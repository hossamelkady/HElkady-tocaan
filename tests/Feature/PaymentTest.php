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

    public function test_paypal_payment_works(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 50, 'status' => 'confirmed']);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', ['order_id' => $order->id, 'payment_method' => 'paypal'])
            ->assertStatus(201)
            ->assertJson(['payment_method' => 'paypal', 'status' => 'successful']);
    }

    public function test_unsupported_payment_method_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 50, 'status' => 'confirmed']);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', ['order_id' => $order->id, 'payment_method' => 'bitcoin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_order_cannot_be_paid_twice(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 50, 'status' => 'confirmed']);
        $payload = ['order_id' => $order->id, 'payment_method' => 'credit_card'];

        $this->withHeader('Authorization', "Bearer $token")->postJson('/api/payments', $payload)->assertStatus(201);
        $this->withHeader('Authorization', "Bearer $token")->postJson('/api/payments', $payload)->assertStatus(409);
    }

    public function test_user_cannot_pay_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create(['user_id' => $owner->id, 'total_amount' => 50, 'status' => 'confirmed']);
        $token = auth('api')->login($other);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', ['order_id' => $order->id, 'payment_method' => 'credit_card'])
            ->assertStatus(404);
    }

    public function test_paid_order_cannot_be_cancelled_or_deleted(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 50, 'status' => 'confirmed']);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', ['order_id' => $order->id, 'payment_method' => 'credit_card'])
            ->assertStatus(201);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertStatus(422);

        $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(400);
    }

    public function test_payments_can_be_listed_and_shown(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 50, 'status' => 'confirmed']);

        $paymentId = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/payments', ['order_id' => $order->id, 'payment_method' => 'credit_card'])
            ->json('id');

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/payments')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/payments/{$paymentId}")
            ->assertStatus(200)
            ->assertJsonPath('order_id', $order->id);
    }
}
