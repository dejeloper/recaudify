<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['username' => 'testuser']);
    }

    public function test_login_returns_token_on_success(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_in', 'user']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'contraseña_incorrecta',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        User::factory()->inactive()->create(['username' => 'inactivo']);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'inactivo',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_login_normalizes_username_to_lowercase(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'TESTUSER',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
    }

    public function test_me_returns_authenticated_user_data(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.username', 'testuser');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_logout_closes_session_successfully(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
