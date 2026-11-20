<?php

namespace Tests\Feature;

use App\Enums\GenerationQuality;
use App\Enums\GenerationSize;
use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Config::set('services.pixelforge.base_url', 'https://api.pixelforge.test/v1');
        Config::set('services.pixelforge.api_key', 'test-api-key');
    }

    public function test_admin_report_requires_admin_authorization(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $response = $this->actingAs($user)->get(route('admin.report'));
        $response->assertStatus(403);
    }

    public function test_admin_report_request_validates_date_format(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Invalid date format
        $response = $this->actingAs($admin)->get(route('admin.report', [
            'start_date' => 'not-a-date',
        ]));
        $response->assertSessionHasErrors(['start_date']);

        // Valid date format
        $validResponse = $this->actingAs($admin)->get(route('admin.report', [
            'start_date' => now()->subDays(7)->format('Y-m-d'),
            'end_date'   => now()->format('Y-m-d'),
        ]));
        $validResponse->assertOk();
    }

    public function test_admin_report_reconciles_daily_metrics_with_pixelforge_usage_api(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user  = User::factory()->create(['is_admin' => false]);

        $today = now()->format('Y-m-d');

        // Create completed generation today
        Generation::create([
            'user_id'                 => $user->id,
            'idempotency_key'         => 'report_test_key_1',
            'prompt'                  => 'A high quality test prompt',
            'status'                  => GenerationStatus::SUCCEEDED,
            'size'                    => GenerationSize::SQUARE,
            'quality'                 => GenerationQuality::MEDIUM,
            'pixelforge_completed_at' => now(),
            'actual_cost_provider'    => '0.040000000',
            'actual_cost_user'        => '0.050000000', // 25% markup: 0.04 * 1.25 = 0.05
        ]);

        // Fake Pixelforge /v1/usage API returning identical cost (exact match)
        Http::fake([
            '*/usage*' => Http::response([
                'data' => [
                    [
                        'date'        => $today,
                        'generations' => 1,
                        'cost_usd'    => '0.040000000',
                    ],
                ],
                'total' => [
                    'generations' => 1,
                    'cost_usd'    => '0.040000000',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.report', [
            'start_date' => $today,
            'end_date'   => $today,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report')
            ->has('dailyReport', 1)
            ->where('dailyReport.0.date', $today)
            ->where('dailyReport.0.generations', 1)
            ->where('dailyReport.0.credit_spend', '0.050000000')
            ->where('dailyReport.0.provider_cost', '0.040000000')
            ->where('dailyReport.0.remote_provider_cost', '0.040000000')
            ->where('dailyReport.0.is_match', true)
            ->where('dailyReport.0.profit', '0.010000000')
            ->has('userReport', 2) // admin + user
            ->where('summary.total_spend', '0.050000000')
            ->where('summary.total_provider_cost', '0.040000000')
            ->where('summary.total_profit', '0.010000000')
            ->where('summary.is_reconciled', true)
        );
    }

    public function test_admin_report_detects_discrepancy_when_provider_api_differs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user  = User::factory()->create(['is_admin' => false]);

        $today = now()->format('Y-m-d');

        // Internal recorded provider cost is 0.040
        Generation::create([
            'user_id'                 => $user->id,
            'idempotency_key'         => 'report_test_key_2',
            'prompt'                  => 'A test prompt for diff',
            'status'                  => GenerationStatus::SUCCEEDED,
            'size'                    => GenerationSize::SQUARE,
            'quality'                 => GenerationQuality::MEDIUM,
            'pixelforge_completed_at' => now(),
            'actual_cost_provider'    => '0.040000000',
            'actual_cost_user'        => '0.050000000',
        ]);

        // Fake Pixelforge reporting 0.045 (discrepancy)
        Http::fake([
            '*/usage*' => Http::response([
                'data' => [
                    [
                        'date'        => $today,
                        'generations' => 1,
                        'cost_usd'    => '0.045000000',
                    ],
                ],
                'total' => [
                    'generations' => 1,
                    'cost_usd'    => '0.045000000',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.report', [
            'start_date' => $today,
            'end_date'   => $today,
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report')
            ->where('dailyReport.0.is_match', false)
            ->where('dailyReport.0.discrepancy', '-0.005000000')
            ->where('summary.is_reconciled', false)
        );
    }
}