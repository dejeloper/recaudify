<?php

namespace Tests\Unit\Services;

use App\Models\LoginAudit;
use App\Models\User;
use App\Services\LoginAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoginAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginAuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoginAuditService();
    }

    private function request(string $userAgent, string $ip = "200.1.2.3"): Request
    {
        return Request::create(
            "/api/auth/login",
            "POST",
            server: [
                "HTTP_USER_AGENT" => $userAgent,
                "REMOTE_ADDR" => $ip,
            ],
        );
    }

    public function test_record_success_parses_windows_desktop(): void
    {
        $user = User::factory()->create(["username" => "jperez"]);
        $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36";

        $audit = $this->service->recordSuccess($user, $this->request($ua));

        $this->assertSame("success", $audit->status);
        $this->assertSame("jperez", $audit->username);
        $this->assertSame("200.1.2.3", $audit->ip_address);
        $this->assertSame("Windows", $audit->os_name);
        $this->assertSame("desktop", $audit->device_type);
    }

    public function test_record_success_parses_iphone_mobile(): void
    {
        $user = User::factory()->create();
        $ua = "Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148";

        $audit = $this->service->recordSuccess($user, $this->request($ua));

        $this->assertSame("iOS", $audit->os_name);
        $this->assertSame("17.2", $audit->os_version);
        $this->assertSame("mobile", $audit->device_type);
    }

    public function test_record_failure_allows_null_user(): void
    {
        $audit = $this->service->recordFailure("fantasma", "invalid_credentials", null, $this->request("UA"));

        $this->assertSame("failed", $audit->status);
        $this->assertSame("invalid_credentials", $audit->reason);
        $this->assertNull($audit->user_id);
        $this->assertSame("fantasma", $audit->username);
    }

    public function test_attach_location_enriches_latest_success(): void
    {
        $user = User::factory()->create();
        LoginAudit::create([
            "user_id" => $user->id,
            "username" => $user->username,
            "status" => "success",
            "logged_at" => now(),
        ]);

        $this->service->attachLocation($user, ["latitude" => 4.711, "longitude" => -74.072, "accuracy" => 10]);

        $this->assertDatabaseHas("login_audits", [
            "user_id" => $user->id,
            "latitude" => 4.711,
            "longitude" => -74.072,
        ]);
    }

    public function test_get_all_filters_by_status(): void
    {
        LoginAudit::create(["username" => "a", "status" => "success", "logged_at" => now()]);
        LoginAudit::create(["username" => "b", "status" => "failed", "logged_at" => now()]);

        $this->assertSame(1, $this->service->getAll(["status" => "failed"])->total());
    }
}
