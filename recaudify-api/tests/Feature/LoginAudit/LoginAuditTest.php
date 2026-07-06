<?php

namespace Tests\Feature\LoginAudit;

use App\Enums\ParameterType;
use App\Models\LoginAudit;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAuditTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["access.view"];

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "pagination_per_page"],
            ["value" => "25", "cast" => "integer"],
        );
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "pagination_max_per_page"],
            ["value" => "100", "cast" => "integer"],
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/login-audits")->assertStatus(401);
    }

    public function test_index_lists_audits_with_expected_shape(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["name" => "Fabiola Guzmán"]);

        LoginAudit::create([
            "user_id" => $user->id,
            "username" => $user->username,
            "status" => "success",
            "reason" => null,
            "ip_address" => "200.1.2.3",
            "os_name" => "Windows",
            "os_version" => "10",
            "device_type" => "desktop",
            "logged_at" => now(),
        ]);

        $response = $this->getJson("/api/login-audits")->assertStatus(200);

        $response->assertJsonPath("success", true);
        $response->assertJsonPath("data.items.0.status", "success");
        $response->assertJsonPath("data.items.0.username", $user->username);
        $response->assertJsonPath("data.items.0.user.name", "Fabiola Guzmán");
        $response->assertJsonPath("data.items.0.os.name", "Windows");
        $response->assertJsonPath("data.items.0.device_type", "desktop");
    }

    public function test_index_filters_by_status(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        LoginAudit::create(["username" => "a", "status" => "success", "logged_at" => now()]);
        LoginAudit::create(["username" => "b", "status" => "failed", "logged_at" => now()]);

        $response = $this->getJson("/api/login-audits?status=failed")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
        $response->assertJsonPath("data.items.0.username", "b");
    }

    public function test_index_filters_by_user_id(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();
        LoginAudit::create(["user_id" => $user->id, "username" => "a", "status" => "success", "logged_at" => now()]);
        LoginAudit::create(["username" => "b", "status" => "success", "logged_at" => now()]);

        $response = $this->getJson("/api/login-audits?user_id={$user->id}")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_paginates_results(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        for ($i = 0; $i < 3; $i++) {
            LoginAudit::create(["username" => "u{$i}", "status" => "success", "logged_at" => now()]);
        }

        $response = $this->getJson("/api/login-audits?per_page=2")->assertStatus(200);

        $response->assertJsonCount(2, "data.items");
        $response->assertJsonPath("data.meta.total", 3);
    }
}
