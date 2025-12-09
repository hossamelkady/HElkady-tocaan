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
}
