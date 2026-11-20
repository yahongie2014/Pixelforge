<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_buy_credit_request_validates_amounts(): void
    {
        $user = User::factory()->create(['balance' => '10.000000000']);

        // Invalid amount
        $response = $this->actingAs($user)->post(route('credits.buy'), [
            'amount' => 999,
        ]);
        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals('10.000000000', $user->fresh()->balance);

        // Valid amount ($25)
        $validResponse = $this->actingAs($user)->post(route('credits.buy'), [
            'amount' => 25,
        ]);
        $validResponse->assertRedirect();
        $validResponse->assertSessionHas('success');
        $this->assertEquals('35.000000000', $user->fresh()->balance);
    }
}
