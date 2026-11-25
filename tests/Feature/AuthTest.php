<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(201)
            ->assertJsonPath('user.email', 'test@example.com');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_validates_input(): void
    {
        $this->postJson('/api/auth/register', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login_and_get_profile(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user']);

        $this->withHeader('Authorization', 'Bearer ' . $response->json('access_token'))
            ->postJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('email', 'login@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_protected_routes_return_401_json_without_token(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
        $this->getJson('/api/payments')->assertStatus(401);
        $this->postJson('/api/auth/me')->assertStatus(401);
    }
}
