<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test', 'email' => 'test@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create(['email' => 'me@example.com', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', ['email' => 'me@example.com', 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'me@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'me@example.com', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_guarded_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/preferences')->assertUnauthorized();
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }
}
