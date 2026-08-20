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

    public function test_login_locks_out_after_max_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/auth/login", [
                "username" => "testuser",
                "password" => "contraseña_incorrecta",
            ])->assertStatus(401);
        }

        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response->assertStatus(429)->assertJsonPath("success", false);
    }

    public function test_login_respects_configured_max_login_attempts(): void
    {
        Parameter::create([
            "type" => "security",
            "key" => "max_login_attempts",
            "value" => "3",
            "cast" => "integer",
            "description" => "Intentos de login fallidos antes de bloquear",
        ]);

        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/auth/login", [
                "username" => "testuser",
                "password" => "contraseña_incorrecta",
            ])->assertStatus(401);
        }

        // Con el parámetro en 3, este 3er intento fallido ya debe agotar el cupo.
        $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "contraseña_incorrecta",
        ])->assertStatus(401);

        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response->assertStatus(429)->assertJsonPath("success", false);
    }

    public function test_login_lockout_disabled_never_blocks(): void
    {
        Parameter::create([
            "type" => "security",
            "key" => "lockout_enabled",
            "value" => "false",
            "cast" => "boolean",
            "description" => "Habilita el bloqueo por intentos fallidos",
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/api/auth/login", [
                "username" => "testuser",
                "password" => "contraseña_incorrecta",
            ])->assertStatus(401);
        }

        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response->assertStatus(200);
    }

    public function test_login_success_clears_failed_attempts_counter(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->postJson("/api/auth/login", [
                "username" => "testuser",
                "password" => "contraseña_incorrecta",
            ])->assertStatus(401);
        }

        $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ])->assertStatus(200);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson("/api/auth/login", [
                "username" => "testuser",
                "password" => "contraseña_incorrecta",
            ])->assertStatus(401);
        }

        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $response->assertStatus(200);
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

    public function test_config_returns_public_data(): void
    {
        $response = $this->getJson("/api/auth/config")->assertStatus(200);

        $response->assertJsonPath("success", true);
        $response->assertJsonStructure([
            "data" => ["geolocalization_login", "login_field", "password_policy"],
        ]);
        $response->assertJsonPath("data.login_field", "username");
    }

    public function test_config_is_public(): void
    {
        $this->getJson("/api/auth/config")->assertStatus(200);
    }

    public function test_change_password_success(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/auth/change-password", [
            "current_password" => "password",
            "password" => "NewSecret123!",
            "password_confirmation" => "NewSecret123!",
        ]);

        $response->assertStatus(200)->assertJsonPath("success", true);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check("NewSecret123!", $this->user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/auth/change-password", [
            "current_password" => "wrong_password",
            "password" => "NewSecret123!",
            "password_confirmation" => "NewSecret123!",
        ]);

        $response->assertStatus(422)->assertJsonStructure(["data" => ["current_password"]]);
    }

    public function test_change_password_requires_confirmation(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/auth/change-password", [
            "current_password" => "password",
            "password" => "NewSecret123!",
        ]);

        $response->assertStatus(422)->assertJsonStructure(["data" => ["password"]]);
    }

    public function test_change_password_requires_authentication(): void
    {
        $this->postJson("/api/auth/change-password", [
            "current_password" => "password",
            "password" => "NewSecret123!",
            "password_confirmation" => "NewSecret123!",
        ])->assertStatus(401);
    }

    public function test_login_rejects_missing_credentials(): void
    {
        $this->postJson("/api/auth/login", [])->assertStatus(422);
    }

    public function test_me_returns_user_with_shift_data(): void
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/auth/me")->assertStatus(200);

        $response->assertJsonStructure([
            "data" => [
                "username",
                "current_shift",
                "shift_status_enabled",
                "password_expired",
                "session_timeout_minutes",
            ],
        ]);
    }

    public function test_refresh_returns_new_token(): void
    {
        $loginResponse = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ]);

        $token = $loginResponse->json("data.token");

        $response = $this->withToken($token)->postJson("/api/auth/refresh")->assertStatus(200);

        $response->assertJsonStructure(["data" => ["token", "token_type", "expires_in"]]);
        $this->assertNotEquals($token, $response->json("data.token"));
    }

    public function test_refresh_requires_authentication(): void
    {
        $this->postJson("/api/auth/refresh")->assertStatus(401);
    }

    public function test_login_returns_session_timeout_minutes(): void
    {
        $response = $this->postJson("/api/auth/login", [
            "username" => "testuser",
            "password" => "password",
        ])->assertStatus(200);

        $response->assertJsonStructure(["data" => ["user" => ["session_timeout_minutes"]]]);
    }
}
