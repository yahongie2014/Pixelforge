<?php

namespace App\Services;

use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use App\Enums\GenerationStatus;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PixelforgeService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $webhookSecret;
    protected float $markupMultiplier;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.pixelforge.base_url', 'https://pixelforge.promptedit.dev/v1'), '/');
        $this->apiKey = config('services.pixelforge.api_key');
        $this->webhookSecret = config('services.pixelforge.webhook_secret');
        $markupPercent = (float) config('services.pixelforge.markup_percent', 25.0);
        $this->markupMultiplier = 1.0 + ($markupPercent / 100.0);
    }

    /**
     * Get published pricing and token definitions.
     */
    public function getPricing(): array
    {
        return Cache::remember('pixelforge_pricing_v1', 300, function () {
            try {
                $response = Http::timeout(6)->get("{$this->baseUrl}/pricing");
                if ($response->successful()) {
                    return $response->json();
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch Pixelforge pricing from API, using fallback defaults', ['error' => $e->getMessage()]);
            }

            return $this->defaultPricing();
        });
    }

    /**
     * Calculate upfront estimated token usage and cost (min, expected, max).
     */
    public function calculateEstimatedCost(string $prompt, string|GenerationSize $size = '1024x1024', string|GenerationQuality $quality = 'medium', array $referenceImages = []): array
    {
        $pricing = $this->getPricing();
        $rates = $pricing['rates'] ?? $this->defaultPricing()['rates'];
        $tokensConfig = $pricing['tokens'] ?? $this->defaultPricing()['tokens'];

        // Normalize enum or string
        $qualityVal = $quality instanceof GenerationQuality ? $quality->value : strtolower($quality);
        $sizeVal    = $size instanceof GenerationSize ? $size->value : strtolower($size);

        // 1. Input text tokens: ceil(characters / 4) with ±15% variance
        $charCount = mb_strlen($prompt);
        $baseTextTokens = (int) max(1, ceil($charCount / ($tokensConfig['characters_per_text_token'] ?? 4)));
        $variance = (float) ($tokensConfig['text_token_variance'] ?? 0.15);
        $minTextTokens = (int) max(1, floor($baseTextTokens * (1.0 - $variance)));
        $maxTextTokens = (int) ceil($baseTextTokens * (1.0 + $variance));

        // 2. Input image tokens: 765 per reference image
        $perImageTokens = (int) ($tokensConfig['per_reference_image'] ?? 765);
        $imageCount = min(4, count($referenceImages));
        $imageTokens = $imageCount * $perImageTokens;

        // 3. Output tokens: exact lookup by quality and size
        $outputTokens = $tokensConfig['output'][$qualityVal][$sizeVal] ?? 1100;

        // 4. Reasoning tokens range by quality
        $reasoningRange = $tokensConfig['reasoning'][$qualityVal] ?? ['min' => 200, 'max' => 2000];
        $minReasoning = (int) ($reasoningRange['min'] ?? 0);
        $maxReasoning = (int) ($reasoningRange['max'] ?? 2000);
        $expectedReasoning = (int) round(($minReasoning + $maxReasoning) / 2);

        // Calculate provider costs
        $costMinProvider = $this->computeProviderCost($minTextTokens, $imageTokens, $outputTokens, $minReasoning, $rates);
        $costExpectedProvider = $this->computeProviderCost($baseTextTokens, $imageTokens, $outputTokens, $expectedReasoning, $rates);
        $costMaxProvider = $this->computeProviderCost($maxTextTokens, $imageTokens, $outputTokens, $maxReasoning, $rates);

        // Apply 25% markup for user prices
        $markupStr = number_format($this->markupMultiplier, 4, '.', '');
        $costMinUser = bcmul($costMinProvider, $markupStr, 9);
        $costExpectedUser = bcmul($costExpectedProvider, $markupStr, 9);
        $costMaxUser = bcmul($costMaxProvider, $markupStr, 9);

        return [
            'tokens' => [
                'text' => [
                    'characters' => $charCount,
                    'baseline' => $baseTextTokens,
                    'min' => $minTextTokens,
                    'max' => $maxTextTokens,
                ],
                'image' => [
                    'count' => $imageCount,
                    'total' => $imageTokens,
                ],
                'output' => $outputTokens,
                'reasoning' => [
                    'min' => $minReasoning,
                    'expected' => $expectedReasoning,
                    'max' => $maxReasoning,
                ],
                'total_expected' => $baseTextTokens + $imageTokens + $outputTokens + $expectedReasoning,
                'total_max' => $maxTextTokens + $imageTokens + $outputTokens + $maxReasoning,
            ],
            'rates' => $rates,
            'provider' => [
                'min' => $costMinProvider,
                'expected' => $costExpectedProvider,
                'max' => $costMaxProvider,
            ],
            'user' => [
                'min' => $costMinUser,
                'expected' => $costExpectedUser,
                'max' => $costMaxUser,
            ],
            'markup_percent' => config('services.pixelforge.markup_percent', 25.0),
        ];
    }

    /**
     * Compute exact provider cost from token counts and rates (formula: tokens * rate / 1M).
     */
    public function computeProviderCost(int $textTokens, int $imageTokens, int $outputTokens, int $reasoningTokens, array $rates): string
    {
        $rateText = $rates['input_text_tokens'] ?? '5.00';
        $rateImage = $rates['input_image_tokens'] ?? '10.00';
        $rateOutput = $rates['output_tokens'] ?? '40.00';
        $rateReasoning = $rates['reasoning_tokens'] ?? '12.50';

        // High-precision arithmetic via BCMath
        $costText = bcmul((string) $textTokens, (string) $rateText, 9);
        $costImage = bcmul((string) $imageTokens, (string) $rateImage, 9);
        $costOutput = bcmul((string) $outputTokens, (string) $rateOutput, 9);
        $costReasoning = bcmul((string) $reasoningTokens, (string) $rateReasoning, 9);

        $totalMicroDollars = bcadd(bcadd($costText, $costImage, 9), bcadd($costOutput, $costReasoning, 9), 9);
        return bcdiv($totalMicroDollars, '1000000', 9);
    }

    /**
     * Compute actual provider and user cost from completed generation usage and rates snapshot.
     */
    public function calculateActualCost(string|GenerationStatus $status, ?array $usage, ?array $rates = null): array
    {
        $statusVal = $status instanceof GenerationStatus ? $status->value : $status;

        if ($statusVal !== GenerationStatus::SUCCEEDED->value || empty($usage)) {
            return [
                'provider_cost' => '0.000000000',
                'user_cost' => '0.000000000',
            ];
        }

        $rates = $rates ?: ($this->getPricing()['rates'] ?? $this->defaultPricing()['rates']);

        $text = (int) ($usage['input_text_tokens'] ?? 0);
        $img = (int) ($usage['input_image_tokens'] ?? 0);
        $out = (int) ($usage['output_tokens'] ?? 0);
        $reasoning = (int) ($usage['reasoning_tokens'] ?? 0);

        $providerCost = $this->computeProviderCost($text, $img, $out, $reasoning, $rates);
        $markupStr = number_format($this->markupMultiplier, 4, '.', '');
        $userCost = bcmul($providerCost, $markupStr, 9);

        return [
            'provider_cost' => $providerCost,
            'user_cost' => $userCost,
        ];
    }

    /**
     * Submit a generation to Pixelforge with Idempotency-Key.
     */
    public function createGeneration(array $params, string $idempotencyKey): array
    {
        if (! $this->apiKey) {
            throw new Exception('Pixelforge API key is not configured.');
        }

        $response = Http::withToken($this->apiKey)
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(20)
            ->post("{$this->baseUrl}/generations", $params);

        return [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'data' => $response->json(),
            'retry_after' => $response->header('Retry-After'),
            'idempotent_replayed' => $response->header('Idempotent-Replayed') === 'true',
        ];
    }

    /**
     * Retrieve a generation by its Pixelforge ID.
     */
    public function getGeneration(string $id): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(10)
            ->get("{$this->baseUrl}/generations/{$id}");

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Get usage report from Pixelforge for date range reconciliation.
     */
    public function getUsage(?string $startDate = null, ?string $endDate = null): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        $query = [];
        if ($startDate) {
            $query['start_date'] = $startDate;
        }
        if ($endDate) {
            $query['end_date'] = $endDate;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(15)
                ->get("{$this->baseUrl}/usage", $query);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Pixelforge /usage API returned non-200: {$response->status()}", [
                'status' => $response->status(),
                'body'   => $response->body(),
                'query'  => $query,
            ]);
        } catch (Exception $e) {
            Log::error("Pixelforge /usage API connection failure: {$e->getMessage()}", [
                'query'     => $query,
                'exception' => get_class($e),
            ]);
        }

        return null;
    }

    /**
     * Verify webhook signature.
     * Header format: Pixelforge-Signature: t=<timestamp>,v1=<signature>
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signatureHeader): bool
    {
        $secret = $this->webhookSecret;

        // If secret is set, strictly enforce signature verification
        if (! $signatureHeader) {
            return false;
        }

        $signatureParts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k && $v) {
                $signatureParts[$k] = $v;
            }
        }

        $timestamp = (int) ($signatureParts['t'] ?? 0);
        $v1Signature = $signatureParts['v1'] ?? '';

        // Reject if timestamp is older or newer than 300 seconds (5 min) to prevent replay
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Pixelforge webhook timestamp expired or out of tolerance', ['timestamp' => $timestamp, 'server_time' => time()]);
            return false;
        }

        if (! $secret) {
            Log::warning('Pixelforge webhook secret not set in .env; skipping cryptographic hash check');
            return true;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawPayload}", $secret);
        return hash_equals($expected, $v1Signature);
    }

    protected function defaultPricing(): array
    {
        return [
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'tokens' => [
                'characters_per_text_token' => 4,
                'text_token_variance' => 0.15,
                'per_reference_image' => 765,
                'output' => [
                    'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                    'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                    'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
                ],
                'reasoning' => [
                    'low' => ['min' => 0, 'max' => 500],
                    'medium' => ['min' => 200, 'max' => 2000],
                    'high' => ['min' => 1000, 'max' => 8000],
                ],
            ],
        ];
    }
}
