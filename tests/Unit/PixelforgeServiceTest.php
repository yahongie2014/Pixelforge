<?php

namespace Tests\Unit;

use App\Services\PixelforgeService;
use Tests\TestCase;

class PixelforgeServiceTest extends TestCase
{
    protected PixelforgeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PixelforgeService();
    }

    public function test_calculate_estimated_cost_matches_docs_formula(): void
    {
        // Worked example from Pixelforge docs:
        // A high quality 1536x1024 image with 400-char prompt and 2 reference images:
        // Text tokens: 400/4 = 100 estimated, up to +15% -> 115 tokens * $5.00/1M = $0.000575000
        // Image tokens: 2 * 765 = 1,530 * $10.00/1M = $0.015300000
        // Output tokens: high 1536x1024 = 6,450 * $40.00/1M = $0.258000000
        // Reasoning tokens: max for high = 8,000 * $12.50/1M = $0.100000000
        // Maximum total provider cost = $0.373875000
        $prompt = str_repeat('a', 400);
        $referenceImages = ['https://example.com/1.png', 'https://example.com/2.png'];

        $estimate = $this->service->calculateEstimatedCost($prompt, '1536x1024', 'high', $referenceImages);

        $this->assertEquals(115, $estimate['tokens']['text']['max']);
        $this->assertEquals(1530, $estimate['tokens']['image']['total']);
        $this->assertEquals(6450, $estimate['tokens']['output']);
        $this->assertEquals(8000, $estimate['tokens']['reasoning']['max']);
        $this->assertEquals('0.373875000', $estimate['provider']['max']);

        // With PromptEdit 25% markup:
        // 0.373875 * 1.25 = 0.467343750
        $this->assertEquals('0.467343750', $estimate['user']['max']);
    }

    public function test_calculate_actual_cost_for_succeeded_generation(): void
    {
        $usage = [
            'input_text_tokens' => 12,
            'input_image_tokens' => 765,
            'output_tokens' => 6450,
            'reasoning_tokens' => 3184,
            'total_tokens' => 10411,
        ];
        $rates = [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ];

        // Provider:
        // 12 * 5 = 60
        // 765 * 10 = 7650
        // 6450 * 40 = 258000
        // 3184 * 12.50 = 39800
        // Total = 305510 / 1M = 0.305510000
        $result = $this->service->calculateActualCost('succeeded', $usage, $rates);

        $this->assertEquals('0.305510000', $result['provider_cost']);

        // User (cost * 1.25):
        // 0.305510000 * 1.25 = 0.381887500
        $this->assertEquals('0.381887500', $result['user_cost']);
    }

    public function test_calculate_actual_cost_for_failed_generation_is_zero(): void
    {
        $result = $this->service->calculateActualCost('failed', ['input_text_tokens' => 100]);

        $this->assertEquals('0.000000000', $result['provider_cost']);
        $this->assertEquals('0.000000000', $result['user_cost']);
    }

    public function test_webhook_signature_verification(): void
    {
        config(['services.pixelforge.webhook_secret' => 'whsec_test_secret_12345']);
        $service = new PixelforgeService();

        $payload = json_encode(['id' => 'evt_test', 'type' => 'ping']);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test_secret_12345');
        $header = "t={$timestamp},v1={$signature}";

        $this->assertTrue($service->verifyWebhookSignature($payload, $header));

        // Replay attack / old timestamp (> 300s)
        $oldTimestamp = time() - 305;
        $oldSignature = hash_hmac('sha256', "{$oldTimestamp}.{$payload}", 'whsec_test_secret_12345');
        $this->assertFalse($service->verifyWebhookSignature($payload, "t={$oldTimestamp},v1={$oldSignature}"));

        // Tampered payload
        $this->assertFalse($service->verifyWebhookSignature($payload . 'tampered', $header));
    }
}
