<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Repositories\Contracts\GenerationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PixelforgeService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Result value-object returned from SubmitGenerationAction.
 */
readonly class SubmitGenerationResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?Generation $generation,
        /** @var array<string, string> */
        public readonly array $errors = [],
        public readonly ?string $successMessage = null,
    ) {}

    public static function ok(Generation $generation, string $message): self
    {
        return new self(true, $generation, successMessage: $message);
    }

    public static function fail(?Generation $generation, string $field, string $message): self
    {
        return new self(false, $generation, errors: [$field => $message]);
    }
}

class SubmitGenerationAction
{
    public function __construct(
        private readonly GenerationRepositoryInterface $generations,
        private readonly UserRepositoryInterface $users,
        private readonly PixelforgeService $pixelforge,
    ) {}

    /**
     * Validate balance, create the local record, and submit to Pixelforge.
     *
     * @param array{prompt: string, size: string, quality: string, reference_images?: string[]} $params
     */
    public function execute(User $user, array $params): SubmitGenerationResult
    {
        // Concurrency lock: prevent accidental double-click duplicate requests
        $lock = Cache::lock("user_submit:{$user->id}", 5);
        if (! $lock->get()) {
            Log::warning("Generation submission blocked: concurrent request lock held", [
                'user_id' => $user->id,
            ]);

            return SubmitGenerationResult::fail(
                null,
                'prompt',
                'A generation request is currently being processed. Please wait a moment.'
            );
        }

        try {
            return $this->processSubmission($user, $params);
        } finally {
            optional($lock)->release();
        }
    }

    private function processSubmission(User $user, array $params): SubmitGenerationResult
    {
        $prompt    = trim($params['prompt']);
        $size      = $params['size'];
        $quality   = $params['quality'];
        $refImages = array_values(array_filter($params['reference_images'] ?? []));

        // 1. Estimate cost (we block on the worst-case max)
        $estimate       = $this->pixelforge->calculateEstimatedCost($prompt, $size, $quality, $refImages);
        $maxUserCost    = $estimate['user']['max'];
        $maxProviderCost = $estimate['provider']['max'];

        // 2. Balance check
        if (! $user->hasSufficientBalance($maxUserCost)) {
            $formattedNeed = number_format((float) $maxUserCost, 4);
            $formattedHave = number_format((float) $user->balance, 4);

            Log::warning("Generation rejected: Insufficient balance", [
                'user_id' => $user->id,
                'email'   => $user->email,
                'needed'  => $maxUserCost,
                'balance' => (string) $user->balance,
            ]);

            return SubmitGenerationResult::fail(
                null,
                'prompt',
                "Insufficient credits. This generation may cost up to \${$formattedNeed}, but your balance is \${$formattedHave}. Please buy credits to proceed."
            );
        }

        // 3. Create local Generation record
        $idempotencyKey = (string) Str::uuid();

        $generation = $this->generations->create([
            'user_id'                => $user->id,
            'idempotency_key'        => $idempotencyKey,
            'status'                 => GenerationStatus::QUEUED,
            'prompt'                 => $prompt,
            'size'                   => $size,
            'quality'                => $quality,
            'reference_images'       => $refImages,
            'metadata'               => [
                'user_id'         => (string) $user->id,
                'user_email'      => $user->email,
                'idempotency_key' => $idempotencyKey,
            ],
            'rates'                  => $estimate['rates'],
            'estimated_cost_provider' => $maxProviderCost,
            'estimated_cost_user'    => $maxUserCost,
            'actual_cost_provider'   => '0.000000000',
            'actual_cost_user'       => '0.000000000',
        ]);

        // 4. Submit to Pixelforge
        try {
            $apiParams = [
                'prompt'   => $prompt,
                'size'     => $size,
                'quality'  => $quality,
                'metadata' => [
                    'local_id'        => (string) $generation->id,
                    'user_id'         => (string) $user->id,
                    'idempotency_key' => $idempotencyKey,
                ],
            ];
            if (! empty($refImages)) {
                $apiParams['reference_images'] = $refImages;
            }

            $result = $this->pixelforge->createGeneration($apiParams, $idempotencyKey);

            if ($result['successful'] && isset($result['data']['id'])) {
                $data = $result['data'];
                $this->generations->update($generation, [
                    'pixelforge_id'        => $data['id'],
                    'status'               => $data['status'] ?? GenerationStatus::QUEUED->value,
                    'rates'                => $data['rates'] ?? $generation->rates,
                    'pixelforge_created_at' => $data['created_at'] ?? now(),
                ]);

                return SubmitGenerationResult::ok(
                    $generation->fresh(),
                    'Generation submitted successfully! It is now being processed.'
                );
            }

            return $this->handleApiError($generation, $result);

        } catch (Exception $e) {
            Log::error("Generation submission network exception: {$e->getMessage()}", [
                'generation_id' => $generation->id,
                'user_id'       => $user->id,
                'prompt'        => $prompt,
                'exception'     => get_class($e),
                'trace'         => collect($e->getTrace())->take(3)->toArray(),
            ]);

            $this->generations->update($generation, [
                'status'        => GenerationStatus::FAILED,
                'error_code'    => 'network_exception',
                'error_message' => $e->getMessage(),
            ]);

            return SubmitGenerationResult::fail(
                $generation,
                'prompt',
                'Connection error contacting Pixelforge: ' . $e->getMessage()
            );
        }
    }

    private function handleApiError(Generation $generation, array $result): SubmitGenerationResult
    {
        $status   = $result['status'] ?? 500;
        $errData  = $result['data']['error'] ?? [];
        $code     = $errData['code'] ?? 'unknown_error';
        $message  = $errData['message'] ?? 'Failed to submit generation to Pixelforge.';

        Log::error("Pixelforge API rejected generation: HTTP {$status} [{$code}] - {$message}", [
            'generation_id' => $generation->id,
            'user_id'       => $generation->user_id,
            'http_status'   => $status,
            'error_code'    => $code,
            'error_message' => $message,
            'api_result'    => $result,
        ]);

        if ($status === 400 || $code === 'content_policy_violation') {
            $this->generations->update($generation, [
                'status'        => GenerationStatus::FAILED,
                'error_code'    => $code,
                'error_message' => $message,
            ]);

            return SubmitGenerationResult::fail($generation, 'prompt', "Pixelforge rejected this prompt: {$message}");
        }

        if ($status === 503 || $code === 'overloaded') {
            $this->generations->update($generation, [
                'status'        => GenerationStatus::FAILED,
                'error_code'    => 'overloaded',
                'error_message' => 'Pixelforge is temporarily overloaded. Please try again in a few moments.',
            ]);

            return SubmitGenerationResult::fail(
                $generation,
                'prompt',
                'Pixelforge is temporarily overloaded (503). You were not charged; please try again shortly.'
            );
        }

        if ($status === 429) {
            $retryAfter = $result['retry_after'] ?? 'a few';
            $this->generations->update($generation, [
                'status'        => GenerationStatus::FAILED,
                'error_code'    => $code,
                'error_message' => "Rate/concurrency limit reached. Retry in {$retryAfter} seconds.",
            ]);

            return SubmitGenerationResult::fail(
                $generation,
                'prompt',
                "Provider rate or concurrency limit reached. Please wait {$retryAfter} seconds before trying again."
            );
        }

        $this->generations->update($generation, [
            'status'        => GenerationStatus::FAILED,
            'error_code'    => $code,
            'error_message' => $message,
        ]);

        return SubmitGenerationResult::fail($generation, 'prompt', "Pixelforge error: {$message}");
    }
}
