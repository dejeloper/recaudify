<?php

namespace Tests\Feature\Activity;

use App\Enums\ParameterType;
use App\Models\Activity;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityBranchTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["audit.view"];
    private const PURGE_PERMISSIONS = ["audit.view", "audit.purge"];

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
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "activity_log_retention_days"],
            ["value" => "365", "cast" => "integer"],
        );
    }

    private function makeActivity(array $attributes = []): Activity
    {
        return Activity::create(
            array_merge(
                [
                    "log_name" => "default",
                    "description" => "evento de prueba",
                    "event" => "created",
                ],
                $attributes,
            ),
        );
    }

    // ── Index branches ─────────────────────────────────────────────

    public function test_index_without_filters_returns_all(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "branch_all"]);
        $this->makeActivity(["log_name" => "branch_all"]);

        $response = $this->getJson("/api/activities?log_name=branch_all")->assertStatus(200);

        $response->assertJsonPath("data.meta.total", 2);
    }

    public function test_index_clamps_per_page_to_max(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        for ($i = 0; $i < 3; $i++) {
            $this->makeActivity();
        }

        $response = $this->getJson("/api/activities?per_page=999")->assertStatus(200);

        $response->assertJsonPath("data.meta.perPage", 100);
    }

    public function test_index_per_page_one_returns_single_item(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity();
        $this->makeActivity();

        $response = $this->getJson("/api/activities?per_page=1")->assertStatus(200);

        $response->assertJsonPath("data.meta.perPage", 1);
        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_only_from_date(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-01-10 10:00:00"]);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-06-15 10:00:00"]);

        $response = $this->getJson("/api/activities?log_name=test&from=2026-06-01")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_only_to_date(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-01-10 10:00:00"]);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-06-15 10:00:00"]);

        $response = $this->getJson("/api/activities?log_name=test&to=2026-03-01")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_user_sistema(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "sistema", "causer_id" => null]);
        $this->makeActivity(["log_name" => "sistema", "causer_type" => User::class, "causer_id" => User::factory()->create()->id]);

        $response = $this->getJson("/api/activities?log_name=sistema&user=sistema")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_subject_id(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $subject = User::factory()->create();
        $this->makeActivity(["log_name" => "test", "subject_type" => User::class, "subject_id" => $subject->id]);
        $this->makeActivity(["log_name" => "test", "subject_type" => User::class, "subject_id" => 9999]);

        $response = $this->getJson("/api/activities?log_name=test&subject_id={$subject->id}")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_user_not_found_returns_empty(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "test", "causer_type" => User::class, "causer_id" => User::factory()->create()->id]);

        $response = $this->getJson("/api/activities?log_name=test&user=noexiste")->assertStatus(200);

        $response->assertJsonCount(0, "data.items");
    }

    // ── Purge branches ─────────────────────────────────────────────

    public function test_purge_without_days_uses_default_retention(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);
        $this->makeActivity(["log_name" => "reciente", "created_at" => now()->subDays(10)]);

        $response = $this->postJson("/api/activities/purge", [])->assertStatus(200);

        $response->assertJsonPath("data.deleted", 1);
        $response->assertJsonPath("data.retention_days", 365);
    }

    public function test_purge_deletes_nothing_when_all_recent(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "ok", "created_at" => now()->subDays(5)]);

        $response = $this->postJson("/api/activities/purge", ["days" => 30])->assertStatus(200);

        $response->assertJsonPath("data.deleted", 0);
        $this->assertEquals(1, Activity::where("log_name", "ok")->count());
    }

    // ── Purge Preview branches ─────────────────────────────────────

    public function test_purge_preview_without_days_uses_default_retention(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $response = $this->getJson("/api/activities/purge/preview")->assertStatus(200);

        $response->assertJsonPath("data.retention_days", 365);
        $response->assertJsonPath("data.deleted", 1);
    }

    public function test_purge_preview_returns_zero_when_nothing_expired(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "ok", "created_at" => now()->subDays(5)]);

        $response = $this->getJson("/api/activities/purge/preview?days=30")->assertStatus(200);

        $response->assertJsonPath("data.deleted", 0);
    }

    // ── Validation branches ────────────────────────────────────────

    public function test_purge_rejects_negative_days(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);

        $this->postJson("/api/activities/purge", ["days" => -5])->assertStatus(422);
    }

    public function test_purge_rejects_zero_days(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);

        $this->postJson("/api/activities/purge", ["days" => 0])->assertStatus(422);
    }

    public function test_purge_preview_rejects_negative_days(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);

        $this->getJson("/api/activities/purge/preview?days=-1")->assertStatus(422);
    }

    public function test_purge_preview_rejects_zero_days(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);

        $this->getJson("/api/activities/purge/preview?days=0")->assertStatus(422);
    }

    public function test_index_rejects_non_date_from(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/activities?from=not-a-date")->assertStatus(422);
    }

    public function test_index_rejects_non_integer_per_page(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/activities?per_page=abc")->assertStatus(422);
    }

    // ── Causer snapshot branches ───────────────────────────────────

    public function test_causer_snapshot_without_relation_returns_frozen_data(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $causer = User::factory()->create(["name" => "Borrado", "username" => "borrado"]);
        $this->actingAs($causer, "api");
        $subject = User::factory()->create(["name" => "Objetivo"]);
        $subject->update(["name" => "Cambiado"]);
        $causer->forceDelete();

        $this->authenticateWith(self::PERMISSIONS);
        $response = $this->getJson("/api/activities?log_name=usuarios")->assertStatus(200);

        $response->assertJsonPath("data.items.0.causer.name", "Borrado");
        $response->assertJsonPath("data.items.0.causer.username", "borrado");
        $response->assertJsonPath("data.items.0.causer.exists", false);
    }

    // ── Purge audit trail ──────────────────────────────────────────

    public function test_purge_records_cutoff_and_retention_in_audit(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $this->postJson("/api/activities/purge", ["days" => 90])->assertStatus(200);

        $audit = Activity::query()->where("log_name", "audit")->latest()->first();
        $this->assertEquals(90, $audit->getExtraProperty("retention_days"));
        $this->assertNotNull($audit->getExtraProperty("cutoff"));
        $this->assertEquals(1, $audit->getExtraProperty("deleted"));
    }
}
