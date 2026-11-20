<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Repositories\Contracts\CreditTransactionRepositoryInterface;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PixelforgeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncGenerationFromApiAction
{
    public function __construct(
        private readonly GenerationRepositoryInterface $generations,
        private readonly UserRepositoryInterface $users,
        private readonly CreditTransactionRepositoryInterface $transactions,
        private readonly PixelforgeService $pixelforge,
    ) {}

    /**
     * Sync a local generation record from a fresh Pixelforge API response.
     * Handles out-of-order status protection and billing on success.
     */
    public function execute(Generation $generation, array $data): void
    {
        $newStatusVal = $data['status'] ?? ($generation->status instanceof GenerationStatus ? $generation->status->value : (string) $generation->status);

        // Never regress from a higher-ranked status
        if (Generation::statusRank($newStatusVal) < Generation::statusRank($generation->status)) {
            return;
        }

        $rates  = $data['rates'] ?? $generation->rates;
        $usage  = $data['usage'] ?? null;
        $costs  = $this->pixelforge->calculateActualCost($newStatusVal, $usage, $rates);

        DB::transaction(function () use ($generation, $data, $newStatusVal, $rates, $usage, $costs) {
            $updates = [
                'status'                   => $newStatusVal,
                'rates'                    => $rates,
                'image_url'                => $data['image_url'] ?? $generation->image_url,
                'image_url_expires_at'     => $data['image_url_expires_at'] ?? $generation->image_url_expires_at,
                'pixelforge_started_at'    => $data['started_at'] ?? $generation->pixelforge_started_at,
                'pixelforge_completed_at'  => $data['completed_at'] ?? $generation->pixelforge_completed_at,
            ];

            if (isset($data['error'])) {
                $updates['error_code']    = $data['error']['code'] ?? null;
                $updates['error_message'] = $data['error']['message'] ?? null;
            }

            if ($usage) {
                $updates['input_text_tokens']  = $usage['input_text_tokens'] ?? 0;
                $updates['input_image_tokens'] = $usage['input_image_tokens'] ?? 0;
                $updates['output_tokens']      = $usage['output_tokens'] ?? 0;
                $updates['reasoning_tokens']   = $usage['reasoning_tokens'] ?? 0;
                $updates['total_tokens']       = $usage['total_tokens'] ?? 0;
            }

            if ($newStatusVal === GenerationStatus::SUCCEEDED->value) {
                $updates['actual_cost_provider'] = $costs['provider_cost'];
                $updates['actual_cost_user']     = $costs['user_cost'];

                $generation->update($updates);

                // Charge the user exactly once
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
                $generation->update($updates);
            } else {
                $generation->update($updates);
            }
        });
    }
}
