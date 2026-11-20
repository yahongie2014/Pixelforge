<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\PixelforgeWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PixelforgeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'whsec_test_abc123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.pixelforge.webhook_secret' => $this->secret]);
    }

    protected function makeSignedWebhook(array $payload, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', "{$timestamp}.{$raw}", $this->secret);

        return [
            'raw' => $raw,
            'headers' => [
                'Content-Type' => 'application/json',
                'Pixelforge-Signature' => "t={$timestamp},v1={$sig}",
                'Pixelforge-Event-Id' => $payload['id'] ?? 'evt_test',
                'Pixelforge-Event-Type' => $payload['type'] ?? 'generation.succeeded',
                'Pixelforge-Delivery-Attempt' => 1,
            ],
        ];
    }

    public function test_rejects_invalid_signature(): void
    {
        $response = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => 't=' . time() . ',v1=invalid_hash',
            ],
            json_encode(['id' => 'evt_1', 'type' => 'ping'])
        );

        $response->assertStatus(400);
    }

    public function test_processes_generation_succeeded_event_and_charges_user(): void
    {
        $user = User::factory()->create(['balance' => '10.000000000']);
        $generation = Generation::create([
            'user_id' => $user->id,
            'pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
            'idempotency_key' => 'idemp_1',
            'status' => 'processing',
            'prompt' => 'A lighthouse on a cliff',
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
        ]);

        $payload = [
            'id' => 'evt_01JC2Z9B7K2M4P6R8T0V3X5Z7A',
            'object' => 'event',
            'type' => 'generation.succeeded',
            'created_at' => '2026-11-20T14:03:29Z',
            'data' => [
                'object' => [
                    'id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
                    'object' => 'generation',
                    'status' => 'succeeded',
                    'image_url' => 'https://pixelforge.promptedit.dev/images/gen_1.png',
                    'image_url_expires_at' => '2026-11-21T14:03:29Z',
                    'usage' => [
                        'input_text_tokens' => 100,
                        'input_image_tokens' => 0,
                        'output_tokens' => 1100,
                        'reasoning_tokens' => 500,
                        'total_tokens' => 1700,
                    ],
                    'rates' => [
                        'input_text_tokens' => '5.00',
                        'input_image_tokens' => '10.00',
                        'output_tokens' => '40.00',
                        'reasoning_tokens' => '12.50',
                    ],
                    'metadata' => [
                        'local_id' => $generation->id,
                        'idempotency_key' => 'idemp_1',
                    ],
                    'completed_at' => '2026-11-20T14:03:29Z',
                ],
            ],
        ];

        $webhook = $this->makeSignedWebhook($payload);

        $response = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => $webhook['headers']['Pixelforge-Signature'],
                'HTTP_PIXELFORGE_EVENT_ID' => $webhook['headers']['Pixelforge-Event-Id'],
            ],
            $webhook['raw']
        );

        $response->assertNoContent();

        $generation->refresh();
        $this->assertEquals(GenerationStatus::SUCCEEDED, $generation->status);
        $this->assertEquals('https://pixelforge.promptedit.dev/images/gen_1.png', $generation->image_url);

        // Provider cost calculation:
        // (100*5 + 0*10 + 1100*40 + 500*12.50) / 1,000,000
        // = (500 + 0 + 44000 + 6250) / 1M = 50750 / 1M = 0.050750000
        $this->assertEqualsWithDelta(0.050750000, (float) $generation->actual_cost_provider, 0.000000001);

        // User cost with 25% markup:
        // 0.050750000 * 1.25 = 0.063437500
        $this->assertEqualsWithDelta(0.063437500, (float) $generation->actual_cost_user, 0.000000001);

        // User balance should be deducted exactly
        // 10.000000000 - 0.063437500 = 9.936562500
        $user->refresh();
        $this->assertEquals('9.936562500', $user->balance);

        // Deduplication test: Send the exact same event again
        $dupResponse = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => $webhook['headers']['Pixelforge-Signature'],
                'HTTP_PIXELFORGE_EVENT_ID' => $webhook['headers']['Pixelforge-Event-Id'],
            ],
            $webhook['raw']
        );

        $dupResponse->assertNoContent();
        $user->refresh();
        // Balance must not be deducted a second time
        $this->assertEquals('9.936562500', $user->balance);
    }

    public function test_out_of_order_event_does_not_regress_succeeded_status(): void
    {
        $user = User::factory()->create();
        $generation = Generation::create([
            'user_id' => $user->id,
            'pixelforge_id' => 'gen_out_of_order',
            'idempotency_key' => 'idemp_ooo',
            'status' => 'succeeded',
            'prompt' => 'Already finished',
            'actual_cost_provider' => '0.050000000',
            'actual_cost_user' => '0.062500000',
        ]);

        // Delayed processing event arrives
        $payload = [
            'id' => 'evt_late_processing',
            'type' => 'generation.processing',
            'data' => [
                'object' => [
                    'id' => 'gen_out_of_order',
                    'status' => 'processing',
                ],
            ],
        ];

        $webhook = $this->makeSignedWebhook($payload);
        $response = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => $webhook['headers']['Pixelforge-Signature'],
            ],
            $webhook['raw']
        );

        $response->assertNoContent();
        $generation->refresh();
        $this->assertEquals(GenerationStatus::SUCCEEDED, $generation->status);
    }

    public function test_failed_generation_is_never_billed(): void
    {
        $user = User::factory()->create(['balance' => '10.000000000']);
        $generation = Generation::create([
            'user_id' => $user->id,
            'pixelforge_id' => 'gen_fail',
            'idempotency_key' => 'idemp_fail',
            'status' => 'processing',
            'prompt' => 'Will fail [fail]',
        ]);

        $payload = [
            'id' => 'evt_fail_1',
            'type' => 'generation.failed',
            'data' => [
                'object' => [
                    'id' => 'gen_fail',
                    'status' => 'failed',
                    'error' => [
                        'code' => 'generation_failed',
                        'message' => 'The model failed to produce an image.',
                    ],
                ],
            ],
        ];

        $webhook = $this->makeSignedWebhook($payload);
        $response = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => $webhook['headers']['Pixelforge-Signature'],
            ],
            $webhook['raw']
        );

        $response->assertNoContent();
        $generation->refresh();
        $user->refresh();

        $this->assertEquals(GenerationStatus::FAILED, $generation->status);
        $this->assertEqualsWithDelta(0.0, (float) $generation->actual_cost_user, 0.000000001);
        $this->assertEqualsWithDelta(0.0, (float) $generation->actual_cost_provider, 0.000000001);
        $this->assertEqualsWithDelta(10.0, (float) $user->balance, 0.000000001);
    }
}
