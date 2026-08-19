<?php

namespace Tests\Feature\Health;

use App\Services\HealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_public(): void
    {
        $this->getJson("/api/health")->assertStatus(200);
    }

    public function test_reports_every_dependency(): void
    {
        $response = $this->getJson("/api/health")->assertStatus(200);

        $response->assertJsonPath("data.status", HealthService::STATUS_OK);
        $response->assertJsonStructure([
            "data" => [
                "status",
                "timestamp",
                "checks" => [
                    "database" => ["status", "duration_ms"],
                    "cache" => ["status", "duration_ms"],
                    "storage" => ["status", "duration_ms"],
                    "queue" => ["status", "duration_ms"],
                ],
            ],
        ]);
    }

    public function test_reports_queue_counters(): void
    {
        config(["queue.default" => "database"]);

        $response = $this->getJson("/api/health")->assertStatus(200);

        $response->assertJsonPath("data.checks.queue.driver", "database");
        $response->assertJsonPath("data.checks.queue.pending", 0);
        $response->assertJsonPath("data.checks.queue.failed", 0);
    }

    public function test_failed_jobs_degrade_but_do_not_take_the_service_down(): void
    {
        config(["queue.default" => "database"]);

        DB::table("failed_jobs")->insert([
            "uuid" => "test-uuid",
            "connection" => "database",
            "queue" => "default",
            "payload" => "{}",
            "exception" => "boom",
            "failed_at" => now(),
        ]);

        $response = $this->getJson("/api/health")->assertStatus(200);

        $response->assertJsonPath("data.status", HealthService::STATUS_DEGRADED);
        $response->assertJsonPath("data.checks.queue.status", HealthService::STATUS_DEGRADED);
        $response->assertJsonPath("data.checks.database.status", HealthService::STATUS_OK);
    }

    public function test_returns_503_when_a_critical_dependency_is_down(): void
    {
        // La caché es crítica: sin ella no funcionan parámetros, permisos ni sesiones.
        Cache::shouldReceive("put")->andThrow(new \RuntimeException("cache caída"));

        $response = $this->getJson("/api/health")->assertStatus(503);

        $response->assertJsonPath("success", false);
        $response->assertJsonPath("data.status", HealthService::STATUS_DOWN);
        $response->assertJsonPath("data.checks.cache.status", HealthService::STATUS_DOWN);
    }

    public function test_does_not_leak_error_details(): void
    {
        Cache::shouldReceive("put")->andThrow(new \RuntimeException("password=secreto host=10.0.0.1"));

        $response = $this->getJson("/api/health")->assertStatus(503);

        $this->assertStringNotContainsString("secreto", $response->getContent());
        $this->assertStringNotContainsString("10.0.0.1", $response->getContent());
    }
}
