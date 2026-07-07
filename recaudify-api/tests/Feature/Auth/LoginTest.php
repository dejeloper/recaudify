<?php

namespace Tests\Feature\Auth;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $this->user = User::factory()
            ->withRole("superadmin")
            ->create(["username" => "testuser", "email" => "testuser@example.com"]);
    }

    public function test_login_returns_token_on_success(): void
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath("success", true)
            ->assertJsonStructure(["data" => ["token", "token_type", "expires_in", "user"]]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "contraseña_incorrecta",
        ]);

        $response->assertStatus(401)->assertJsonPath("success", false);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        User::factory()
            ->inactive()
            ->create(["username" => "inactivo"]);

        $response = $this->postJson("/api/auth/login", [
            "username" => "inactivo",
            "password" => "password",
        ]);

        $response->assertStatus(403)->assertJsonPath("success", false);
    }

    public function test_login_normalizes_username_to_lowercase(): void
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => "TESTUSER",
            "password" => "password",
        ]);

        $response->assertStatus(200);
    }

    public function test_login_with_username_field_rejects_email(): void
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => $this->user->email,
            "password" => "password",
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_email_field_authenticates_by_email(): void
    {
        Parameter::create([
            "type" => "authentication",
            "key" => "login_field",
            "value" => "email",
            "cast" => "string",
            "description" => "Campo usado para autenticar",
        ]);

        $response = $this->postJson("/api/auth/login", [
            "username" => $this->user->email,
            "password" => "password",
        ]);

        $response->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_login_with_email_field_rejects_username(): void
    {
        Parameter::create([
            "type" => "authentication",
            "key" => "login_field",
            "value" => "email",
            "cast" => "string",
            "description" => "Campo usado para autenticar",
        ]);

        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user_data(): void
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/auth/me");

        $response->assertStatus(200)->assertJsonPath("data.username", "testuser");
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson("/api/auth/me")->assertStatus(401);
    }

    public function test_logout_closes_session_successfully(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/auth/logout");

        $response->assertStatus(200)->assertJsonPath("success", true);
    }
}
