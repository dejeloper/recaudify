<?php

namespace Tests\Unit\Services;

use App\Enums\ParameterType;
use App\Models\Activity;
use App\Models\Parameter;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "activity_log_retention_days"],
            ["value" => "365", "cast" => "integer"],
        );

        $this->service = $this->app->make(ActivityService::class);
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

    public function test_get_all_filters_by_causer_username(): void
    {
        $user = User::factory()->create(["username" => "jperez"]);
        $other = User::factory()->create(["username" => "otro"]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $other->id]);

        $result = $this->service->getAll(["user" => "jperez"]);

        $this->assertSame(1, $result->total());
        $this->assertSame($user->id, $result->items()[0]->causer_id);
    }

    public function test_get_all_filters_by_causer_username_unknown_returns_none(): void
    {
        $user = User::factory()->create(["username" => "jperez"]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);

        $result = $this->service->getAll(["user" => "no-existe"]);

        $this->assertSame(0, $result->total());
    }

    public function test_get_all_filters_by_sistema_user(): void
    {
        // Nota: crear un User dispara un log automático (log_name "usuarios") vía
        // LogsModelActivity, por eso las actividades manuales usan log_name "default"
        // para poder aislarlas del ruido generado por el factory.
        $user = User::factory()->create();
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity();

        $result = $this->service->getAll(["user" => "sistema", "log_name" => "default"]);

        $this->assertSame(1, $result->total());
        $this->assertNull($result->items()[0]->causer_id);
    }

    public function test_get_all_filters_by_log_name(): void
    {
        $this->makeActivity(["log_name" => "security"]);
        $this->makeActivity(["log_name" => "default"]);

        $result = $this->service->getAll(["log_name" => "security"]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_all_filters_by_model(): void
    {
        // Ver nota en test_get_all_filters_by_sistema_user sobre log_name "default".
        $user = User::factory()->create();
        $this->makeActivity(["subject_type" => User::class, "subject_id" => $user->id]);
        $this->makeActivity();

        $result = $this->service->getAll(["model" => "User", "log_name" => "default"]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_all_attaches_subject_label_for_known_model(): void
    {
        $user = User::factory()->create(["name" => "Fabiola Guzmán"]);
        $this->makeActivity(["subject_type" => User::class, "subject_id" => $user->id]);

        $result = $this->service->getAll();

        $this->assertSame("Fabiola Guzmán", $result->items()[0]->subject_label);
    }

    public function test_get_all_skips_labels_for_unknown_subject_class(): void
    {
        $this->makeActivity(["subject_type" => "App\\Models\\NoExiste", "subject_id" => 1]);

        $result = $this->service->getAll();

        $this->assertNull($result->items()[0]->subject_label ?? null);
    }

    // ── purge() ────────────────────────────────────────────────────

    public function test_purge_with_explicit_days_deletes_old_records(): void
    {
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);
        $this->makeActivity(["log_name" => "reciente", "created_at" => now()->subDays(5)]);

        $result = $this->service->purge(30);

        $this->assertSame(1, $result["deleted"]);
        $this->assertSame(30, $result["retention_days"]);
        $this->assertEquals(0, Activity::where("log_name", "viejo")->count());
        $this->assertEquals(1, Activity::where("log_name", "reciente")->count());
    }

    public function test_purge_without_days_uses_parameter_default(): void
    {
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);
        $this->makeActivity(["log_name" => "reciente", "created_at" => now()->subDays(10)]);

        $result = $this->service->purge();

        $this->assertSame(365, $result["retention_days"]);
        $this->assertSame(1, $result["deleted"]);
    }

    public function test_purge_returns_zero_when_nothing_expired(): void
    {
        $this->makeActivity(["log_name" => "ok", "created_at" => now()->subDays(5)]);

        $result = $this->service->purge(30);

        $this->assertSame(0, $result["deleted"]);
    }

    public function test_purge_logs_audit_activity(): void
    {
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $this->service->purge(30);

        $audit = Activity::query()->where("log_name", "audit")->latest()->first();
        $this->assertNotNull($audit);
        $this->assertSame("purgó el log de actividad", $audit->description);
        $this->assertSame(1, $audit->getExtraProperty("deleted"));
    }

    // ── previewPurge() ─────────────────────────────────────────────

    public function test_preview_purge_with_explicit_days(): void
    {
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);
        $this->makeActivity(["log_name" => "reciente", "created_at" => now()->subDays(5)]);
        $countBefore = Activity::count();

        $result = $this->service->previewPurge(30);

        $this->assertSame(1, $result["deleted"]);
        $this->assertSame(30, $result["retention_days"]);
        $this->assertEquals($countBefore, Activity::count(), "Preview must not delete anything");
    }

    public function test_preview_purge_without_days_uses_parameter_default(): void
    {
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $result = $this->service->previewPurge();

        $this->assertSame(365, $result["retention_days"]);
        $this->assertSame(1, $result["deleted"]);
    }

    public function test_preview_purge_returns_zero_when_nothing_expired(): void
    {
        $this->makeActivity(["log_name" => "ok", "created_at" => now()->subDays(5)]);

        $result = $this->service->previewPurge(30);

        $this->assertSame(0, $result["deleted"]);
    }

    // ── attachSubjectLabels branches ───────────────────────────────

    public function test_get_all_skips_labels_when_model_has_no_name_column(): void
    {
        $schedule = UserSchedule::create([
            "user_id" => User::factory()->create()->id,
            "day_of_week" => 1,
            "start_time" => "09:00",
            "end_time" => "18:00",
            "show_status" => true,
        ]);
        $this->makeActivity(["subject_type" => UserSchedule::class, "subject_id" => $schedule->id]);

        $result = $this->service->getAll();

        $this->assertNull($result->items()[0]->subject_label ?? null);
    }

    public function test_get_all_attaches_label_for_soft_deleted_subject(): void
    {
        $user = User::factory()->create(["name" => "Borrado"]);
        $this->makeActivity(["subject_type" => User::class, "subject_id" => $user->id]);
        $user->delete();

        $result = $this->service->getAll();

        $this->assertSame("Borrado", $result->items()[0]->subject_label);
    }

    // ── resolveUserFilter branches ─────────────────────────────────

    public function test_get_all_with_no_user_filter(): void
    {
        $this->makeActivity(["log_name" => "branch_a"]);
        $this->makeActivity(["log_name" => "branch_b"]);

        $result = $this->service->getAll(["log_name" => "branch_a"]);

        $this->assertSame(1, $result->total());
    }
}
