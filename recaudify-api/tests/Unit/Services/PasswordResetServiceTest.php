<?php

namespace Tests\Unit\Services;

use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    private PasswordResetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PasswordResetService::class);
    }

    private function setParameter(string $key, string $value): void
    {
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Authentication->value, "key" => $key],
            ["value" => $value, "cast" => "string"],
        );
    }

    public function test_reset_uses_fixed_password_when_configured(): void
    {
        $this->setParameter("reset_password_mode", "fixed");
        $this->setParameter("reset_password_fixed_value", "ClaveFija123");
        $user = User::factory()->create(["password" => Hash::make("original1234")]);

        $password = $this->service->reset($user, null);

        $this->assertSame("ClaveFija123", $password);
        $this->assertTrue(Hash::check("ClaveFija123", $user->fresh()->password));
    }

    public function test_reset_generates_random_password_when_mode_is_random(): void
    {
        $this->setParameter("reset_password_mode", "random");
        $user = User::factory()->create(["password" => Hash::make("original1234")]);

        $password = $this->service->reset($user, null);

        $this->assertSame(12, strlen($password));
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_reset_falls_back_to_random_when_fixed_value_is_empty(): void
    {
        $this->setParameter("reset_password_mode", "fixed");
        $this->setParameter("reset_password_fixed_value", "");
        $user = User::factory()->create(["password" => Hash::make("original1234")]);

        $password = $this->service->reset($user, null);

        $this->assertSame(12, strlen($password));
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_reset_falls_back_to_random_when_mode_is_missing(): void
    {
        $user = User::factory()->create(["password" => Hash::make("original1234")]);

        $password = $this->service->reset($user, null);

        $this->assertSame(12, strlen($password));
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_reset_logs_security_event_with_user_ids(): void
    {
        Log::shouldReceive("channel")->once()->with("security")->andReturnSelf();
        Log::shouldReceive("warning")
            ->once()
            ->with(
                "Contraseña reseteada por administrador",
                \Mockery::on(fn(array $context) => $context["by_user_id"] === 7),
            );

        $user = User::factory()->create(["password" => Hash::make("original1234")]);

        $this->service->reset($user, 7);
    }
}
