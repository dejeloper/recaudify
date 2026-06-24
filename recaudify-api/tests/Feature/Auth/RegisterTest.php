<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_user_with_valid_data(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Juan Pérez",
            "username" => "juan.perez",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(201)->assertJsonPath("success", true)->assertJsonPath("data.username", "juan.perez");
    }

    public function test_normalizes_username_to_lowercase(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Juan",
            "username" => "JUAN.PEREZ",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(201)->assertJsonPath("data.username", "juan.perez");
    }

    public function test_fails_when_username_is_already_taken(): void
    {
        $payload = [
            "name" => "Juan",
            "username" => "juan",
            "password" => "password123",
            "password_confirmation" => "password123",
        ];

        $this->postJson("/api/auth/register", $payload)->assertStatus(201);
        $this->postJson("/api/auth/register", $payload)->assertStatus(422);
    }

    public function test_fails_when_password_not_confirmed(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Juan",
            "username" => "juan",
            "password" => "password123",
            "password_confirmation" => "diferente",
        ]);

        $response->assertStatus(422);
    }

    public function test_fails_when_username_has_invalid_characters(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Juan",
            "username" => "juan perez",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(422);
    }

    public function test_accepts_registration_without_email(): void
    {
        $response = $this->postJson("/api/auth/register", [
            "name" => "Juan",
            "username" => "juan",
            "password" => "password123",
            "password_confirmation" => "password123",
        ]);

        $response->assertStatus(201);
    }
}
