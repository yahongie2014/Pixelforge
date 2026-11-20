<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Enums\PixelforgeEventType;
use App\Models\Generation;
use App\Repositories\Contracts\CreditTransactionRepositoryInterface;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WebhookEventRepositoryInterface;
use App\Services\PixelforgeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleWebhookEventAction
{
    public function __construct(
        private readonly GenerationRepositoryInterface $generations,
        private readonly UserRepositoryInterface $users,
        private readonly WebhookEventRepositoryInterface $webhookEvents,
        private readonly CreditTransactionRepositoryInterface $transactions,
        private readonly PixelforgeService $pixelforge,
    ) {}

    /**
     * Process an already-verified, decoded Pixelforge webhook event.
     *
     * @return bool  true = fully processed, false = ignored (already seen / unknown)
     */
    public function execute(array $event, int $deliveryAttempt = 1): bool
    {
        $eventId   = (string) $event['id'];
        $eventType = (string) $event['type'];

        // 1. Strict deduplication — never process the same event ID twice
        if ($this->webhookEvents->existsByEventId($eventId)) {
            Log::info("Pixelforge webhook duplicate ignored: {$eventId}");

            return false;
        }

        // 2. Handle simple ping events
        if ($eventType === PixelforgeEventType::PING->value) {
            $this->webhookEvents->create([
                'event_id'         => $eventId,
                'event_type'       => $eventType,
                'delivery_attempt' => $deliveryAttempt,
                'payload'          => $event,
                'processed_at'     => now(),
            ]);

            return true;
        }

        $dataObj       = $event['data']['object'] ?? [];
        $pixelforgeId  = $dataObj['id'] ?? null;
        $meta          = $dataObj['metadata'] ?? [];
        $localId       = $meta['local_id'] ?? null;
        $idempotencyKey = $meta['idempotency_key'] ?? null;

        // 3. Resolve generation record via multiple lookup strategies
        $generation = $this->resolveGeneration($pixelforgeId, $idempotencyKey, $localId, $dataObj, $meta, $eventId);

        if (! $generation) {
            Log::warning("Pixelforge webhook: could not resolve generation for event {$eventId}");

            return false;
        }

        // 4. Out-of-order guard
        $currentStatusVal = $generation->status instanceof GenerationStatus ? $generation->status->value : (string) $generation->status;
        $parsedEventType  = PixelforgeEventType::tryFrom($eventType);
        $newStatusVal     = $dataObj['status'] ?? $parsedEventType?->targetStatus()?->value ?? $currentStatusVal;

        $currentRank = Generation::statusRank($generation->status);
        $newRank     = Generation::statusRank($newStatusVal);

        if ($newRank < $currentRank) {
            Log::info("Pixelforge webhook: ignoring out-of-order {$currentStatusVal} → {$newStatusVal} for gen #{$generation->id}");

            // Still record so Pixelforge stops retrying
            $this->webhookEvents->create([
                'event_id'         => $eventId,
                'event_type'       => $eventType,
                'pixelforge_id'    => $pixelforgeId,
                'delivery_attempt' => $deliveryAttempt,
                'payload'          => $event,
                'processed_at'     => now(),
            ]);

            return true;
        }

        // 5. Atomic state update + billing
        DB::transaction(function () use (
            $generation, $dataObj, $newStatusVal, $pixelforgeId,
            $eventId, $eventType, $deliveryAttempt, $event
        ) {
            $rates = $dataObj['rates'] ?? $generation->rates;
            $usage = $dataObj['usage'] ?? null;
            $costs = $this->pixelforge->calculateActualCost($newStatusVal, $usage, $rates);

            $updates = [
                'status'                  => $newStatusVal,
                'rates'                   => $rates,
                'image_url'               => $dataObj['image_url'] ?? $generation->image_url,
                'image_url_expires_at'    => $dataObj['image_url_expires_at'] ?? $generation->image_url_expires_at,
                'pixelforge_started_at'   => $dataObj['started_at'] ?? $generation->pixelforge_started_at,
                'pixelforge_completed_at' => $dataObj['completed_at'] ?? $generation->pixelforge_completed_at,
            ];

            if ($pixelforgeId) {
                $updates['pixelforge_id'] = $pixelforgeId;
            }

            if (isset($dataObj['error'])) {
                $updates['error_code']    = $dataObj['error']['code'] ?? null;
                $updates['error_message'] = $dataObj['error']['message'] ?? null;
            }

            if ($usage) {
                $updates['input_text_tokens']  = (int) ($usage['input_text_tokens'] ?? 0);
                $updates['input_image_tokens'] = (int) ($usage['input_image_tokens'] ?? 0);
                $updates['output_tokens']      = (int) ($usage['output_tokens'] ?? 0);
                $updates['reasoning_tokens']   = (int) ($usage['reasoning_tokens'] ?? 0);
                $updates['total_tokens']       = (int) ($usage['total_tokens'] ?? 0);
            }

            if ($newStatusVal === GenerationStatus::SUCCEEDED->value) {
                $updates['actual_cost_provider'] = $costs['provider_cost'];
                $updates['actual_cost_user']     = $costs['user_cost'];

                $this->generations->update($generation, $updates);

                // Charge exactly once
                if (
                    ! $this->transactions->existsForGeneration($generation->id)
                    && bccomp($costs['user_cost'], '0', 9) > 0
                ) {
                    $this->users->deductBalance(
                        $generation->user,
                        $costs['user_cost'],
                        $generation,
                        "Image generation #{$generation->id}"
                    );
                }
            } elseif ($newStatusVal === GenerationStatus::FAILED->value) {
                $updates['actual_cost_provider'] = '0.000000000';
                $updates['actual_cost_user']     = '0.000000000';
                $this->generations->update($generation, $updates);
            } else {
                $this->generations->update($generation, $updates);
            }

            $this->webhookEvents->create([
                'event_id'         => $eventId,
                'event_type'       => $eventType,
                'pixelforge_id'    => $pixelforgeId,
                'delivery_attempt' => $deliveryAttempt,
                'payload'          => $event,
                'processed_at'     => now(),
            ]);
        });

        return true;
    }

    /**
     * Try to find the matching local generation via multiple strategies.
     * Covers: webhook arriving before POST response, ID stored after, etc.
     */
    private function resolveGeneration(
        ?string $pixelforgeId,
        ?string $idempotencyKey,
        ?string $localId,
        array $dataObj,
        array $meta,
        string $eventId,
    ): ?Generation {
        if ($pixelforgeId) {
            $gen = $this->generations->findByPixelforgeId($pixelforgeId);
            if ($gen) {
                return $gen;
            }
        }

        if ($idempotencyKey) {
            $gen = $this->generations->findByIdempotencyKey($idempotencyKey);
            if ($gen) {
                return $gen;
            }
        }

        if ($localId) {
            $gen = $this->generations->findById((int) $localId);
            if ($gen) {
                return $gen;
            }
        }

        // Last resort: create a placeholder if we have a user_id in metadata
        $userId = $meta['user_id'] ?? null;
        if ($userId) {
            return $this->generations->create([
                'user_id'         => (int) $userId,
                'pixelforge_id'   => $pixelforgeId,
                'idempotency_key' => $idempotencyKey ?: "wh_{$eventId}",
                'status'          => GenerationStatus::QUEUED,
                'prompt'          => $dataObj['prompt'] ?? 'Image generation',
                'size'            => $dataObj['size'] ?? '1024x1024',
                'quality'         => $dataObj['quality'] ?? 'medium',
                'rates'           => $dataObj['rates'] ?? null,
                'actual_cost_provider' => '0.000000000',
                'actual_cost_user'     => '0.000000000',
                'estimated_cost_provider' => '0.000000000',
                'estimated_cost_user'  => '0.000000000',
            ]);
        }

        return null;
    }
}
