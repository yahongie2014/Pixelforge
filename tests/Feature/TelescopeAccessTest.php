<?php

namespace Tests\Feature;

use App\Actions\SubmitGenerationAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelescopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_is_denied_by_telescope_gate(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'email' => 'regular@example.com']);

        $this->assertFalse(Gate::forUser($user)->allows('viewTelescope'));
    }

    public function test_admin_user_is_authorized_by_telescope_gate(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue(Gate::forUser($admin)->allows('viewTelescope'));
    }

    public function test_operator_admin_email_is_authorized_by_telescope_gate(): void
    {
        $operator = User::factory()->create(['is_admin' => false, 'email' => 'admin@promptedit.com']);

        $this->assertTrue(Gate::forUser($operator)->allows('viewTelescope'));
    }

    public function test_request_failure_logger_middleware_logs_client_and_server_errors(): void
    {
        Log::spy();

        $user = User::factory()->create(['is_admin' => false]);

        // Trigger a 403 Forbidden by accessing admin report as a regular user
        $response = $this->actingAs($user)->get(route('admin.report'));
        $response->assertStatus(403);

        Log::shouldHaveReceived('notice')->once();
    }

    public function test_redis_concurrency_lock_prevents_duplicate_simultaneous_generation_submissions(): void
    {
        $user = User::factory()->create(['balance' => '50.000000000']);

        // Simulate an active Redis lock held by an in-flight request
        $lock = Cache::lock("user_submit:{$user->id}", 10);
        $lock->get();

        $action = app(SubmitGenerationAction::class);
        $result = $action->execute($user, [
            'prompt'  => 'Test prompt under lock',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        $this->assertFalse($result->successful);
        $this->assertArrayHasKey('prompt', $result->errors);
        $this->assertStringContainsString('currently being processed', $result->errors['prompt']);

        $lock->release();
    }
}
