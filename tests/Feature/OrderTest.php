<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_order()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/orders', [
                'items' => [
                    [
                        'product_name' => 'Product 1',
                        'quantity' => 2,
                        'price' => 50,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'total_amount', 'status', 'items']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_amount' => 100,
        ]);
    }

    public function test_user_can_list_orders()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Order::create([
            'user_id' => $user->id,
            'total_amount' => 100,
            'status' => 'pending'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_order_total_is_calculated_from_items(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/orders', [
                'items' => [
                    ['product_name' => 'A', 'quantity' => 2, 'price' => 10.5],
                    ['product_name' => 'B', 'quantity' => 1, 'price' => 4.25],
                ],
            ])->assertStatus(201);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'total_amount' => 25.25]);
    }

    public function test_order_creation_requires_items(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_user_cannot_see_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create(['user_id' => $owner->id, 'total_amount' => 10, 'status' => 'pending']);
        $token = auth('api')->login($other);

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_order_can_be_confirmed_then_cancelled(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'pending']);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'confirmed');

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_cancelled_order_cannot_be_reopened(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'cancelled']);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertStatus(422);
    }

    public function test_update_rejects_invalid_status(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'pending']);

        $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/orders/{$order->id}", ['status' => 'shipped'])
            ->assertStatus(422);
    }

    public function test_order_can_be_deleted_when_it_has_no_payments(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'pending']);

        $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'pending']);
        Order::create(['user_id' => $user->id, 'total_amount' => 20, 'status' => 'confirmed']);

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/orders?status=confirmed')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
