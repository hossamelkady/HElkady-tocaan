<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_process_payment_for_confirmed_order()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 100,
            'status' => 'confirmed'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card'
            ]);

        $response->assertStatus(201)
            ->assertJson(['status' => 'successful']);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 100,
            'status' => 'successful'
        ]);
    }

    public function test_user_cannot_process_payment_for_pending_order()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 100,
            'status' => 'pending'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', [
                'order_id' => $order->id,
                'payment_method' => 'credit_card'
            ]);

        $response->assertStatus(400);
    }
}
