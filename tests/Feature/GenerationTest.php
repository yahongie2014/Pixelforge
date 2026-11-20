<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_generations_index_with_optional_filter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('generations.index', ['status' => 'succeeded']));
        $response->assertOk();

        // Invalid status filter fails validation
        $invalidResponse = $this->actingAs($user)->get(route('generations.index', ['status' => 'invalid_status']));
        $invalidResponse->assertSessionHasErrors(['status']);
    }

    public function test_estimate_request_validation(): void
    {
        $user = User::factory()->create();

        // Valid estimate
        $response = $this->actingAs($user)->getJson(route('generations.estimate', [
            'prompt'  => 'A futuristic neon skyline',
            'size'    => '1024x1024',
            'quality' => 'high',
        ]));

        $response->assertOk();
        $response->assertJsonStructure(['estimate', 'can_afford', 'user_balance']);

        // Invalid size
        $badSize = $this->actingAs($user)->getJson(route('generations.estimate', [
            'size' => '9999x9999',
        ]));
        $badSize->assertStatus(422)->assertJsonValidationErrors(['size']);
    }

    public function test_store_generation_request_validation(): void
    {
        $user = User::factory()->create();

        // Missing prompt
        $response = $this->actingAs($user)->post(route('generations.store'), [
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);
        $response->assertSessionHasErrors(['prompt']);

        // Invalid quality
        $badQuality = $this->actingAs($user)->post(route('generations.store'), [
            'prompt'  => 'A test prompt',
            'size'    => '1024x1024',
            'quality' => 'ultra_super',
        ]);
        $badQuality->assertSessionHasErrors(['quality']);
    }

    public function test_generation_policy_authorizes_owner_and_forbids_others(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $generation = Generation::create([
            'user_id' => $owner->id,
            'idempotency_key' => 'test_key',
            'status' => 'queued',
            'prompt' => 'A lighthouse',
        ]);

        // Owner can access status
        $ownerResp = $this->actingAs($owner)->getJson(route('generations.status', $generation));
        $ownerResp->assertOk();

        // Other user is forbidden by policy
        $otherResp = $this->actingAs($otherUser)->getJson(route('generations.status', $generation));
        $otherResp->assertStatus(403);

        // Admin can access any generation status
        $adminResp = $this->actingAs($admin)->getJson(route('generations.status', $generation));
        $adminResp->assertOk();
    }
}
