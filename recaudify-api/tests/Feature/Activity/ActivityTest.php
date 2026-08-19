<?php

namespace Tests\Feature\Activity;

use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Activity;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/activities")->assertStatus(401);
    }

    public function test_index_lists_activities_with_expected_shape(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $causer = User::factory()->create(["name" => "Fabiola Guzmán"]);
        $subject = User::factory()->create(["name" => "Cliente Uno"]);

        $this->makeActivity([
            "log_name" => "test",
            "causer_type" => User::class,
            "causer_id" => $causer->id,
            "subject_type" => User::class,
            "subject_id" => $subject->id,
            "properties" => ["attributes" => ["name" => "Nuevo"], "old" => ["name" => "Viejo"]],
        ]);

        $response = $this->getJson("/api/activities?log_name=test")->assertStatus(200);

        $response->assertJsonPath("success", true);
        $response->assertJsonPath("data.items.0.model", "User");
        $response->assertJsonPath("data.items.0.subject.label", "Cliente Uno");
        $response->assertJsonPath("data.items.0.causer.name", "Fabiola Guzmán");
        $response->assertJsonPath("data.items.0.changes.0.field", "name");
        $response->assertJsonPath("data.items.0.changes.0.old", "Viejo");
        $response->assertJsonPath("data.items.0.changes.0.new", "Nuevo");
    }

    public function test_index_filters_by_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["username" => "jperez"]);
        $this->makeActivity(["log_name" => "test", "causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity(["log_name" => "test"]);

        $response = $this->getJson("/api/activities?log_name=test&user=jperez")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_paginates_results(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        for ($i = 0; $i < 3; $i++) {
            $this->makeActivity(["log_name" => "test"]);
        }

        $response = $this->getJson("/api/activities?log_name=test&per_page=2")->assertStatus(200);

        $response->assertJsonCount(2, "data.items");
        $response->assertJsonPath("data.meta.total", 3);
    }

    public function test_index_filters_by_date_range(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-01-10 10:00:00"]);
        $this->makeActivity(["log_name" => "test", "created_at" => "2026-06-15 10:00:00"]);

        $response = $this->getJson(
            "/api/activities?log_name=test&from=2026-06-01&to=2026-06-30 23:59:59",
        )->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_rejects_inverted_date_range(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/activities?from=2026-06-30&to=2026-06-01")->assertStatus(422);
    }

    public function test_causer_snapshot_survives_user_deletion(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $causer = User::factory()->create(["name" => "Ana Ruiz", "username" => "aruiz"]);

        $this->actingAs($causer, "api");
        $subject = User::factory()->create(["name" => "Cliente Uno"]);
        $subject->update(["name" => "Cliente Editado"]);

        $causer->forceDelete();

        $this->authenticateWith(self::PERMISSIONS);
        $response = $this->getJson("/api/activities?log_name=usuarios")->assertStatus(200);

        $response->assertJsonPath("data.items.0.causer.name", "Ana Ruiz");
        $response->assertJsonPath("data.items.0.causer.username", "aruiz");
        $response->assertJsonPath("data.items.0.causer.exists", false);
    }

    public function test_causer_snapshot_survives_user_rename(): void
    {
        $causer = User::factory()->create(["name" => "Nombre Viejo", "username" => "viejo"]);
        $this->actingAs($causer, "api");

        $subject = User::factory()->create();
        $subject->update(["name" => "Otro"]);

        $causer->update(["name" => "Nombre Nuevo", "username" => "nuevo"]);

        $activity = Activity::query()->where("subject_id", $subject->id)->where("event", "updated")->firstOrFail();

        $this->assertEquals("Nombre Viejo", $activity->causer_name);
        $this->assertEquals("viejo", $activity->causer_username);
    }

    public function test_purge_requires_permission(): void
    {
        // authenticateWith() crea un superadmin, que salta toda verificación por Gate::before.
        // Para probar el permiso hace falta un usuario común con audit.view pero sin audit.purge.
        Permission::firstOrCreate(["name" => "audit.view", "guard_name" => "api"]);
        Permission::firstOrCreate(["name" => "audit.purge", "guard_name" => "api"]);

        $role = Role::firstOrCreate(["name" => "auditor", "guard_name" => "api"]);
        $role->syncPermissions(["audit.view"]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->withRole("auditor")->create();
        UserSchedule::create([
            "user_id" => $user->id,
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "00:00",
            "end_time" => "23:59",
            "show_status" => true,
        ]);
        $this->actingAs($user, "api");

        $this->postJson("/api/activities/purge")->assertStatus(403);
        $this->getJson("/api/activities")->assertStatus(200);
    }

    public function test_purge_deletes_only_expired_activities(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);
        $this->makeActivity(["log_name" => "reciente", "created_at" => now()->subDays(10)]);

        $response = $this->postJson("/api/activities/purge")->assertStatus(200);

        $response->assertJsonPath("data.deleted", 1);
        $this->assertEquals(0, Activity::where("log_name", "viejo")->count());
        $this->assertEquals(1, Activity::where("log_name", "reciente")->count());
    }

    public function test_purge_accepts_explicit_days(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "test", "created_at" => now()->subDays(30)]);

        $response = $this->postJson("/api/activities/purge", ["days" => 7])->assertStatus(200);

        $response->assertJsonPath("data.deleted", 1);
        $response->assertJsonPath("data.retention_days", 7);
    }

    public function test_purge_is_itself_audited(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $this->postJson("/api/activities/purge")->assertStatus(200);

        $audit = Activity::query()->where("log_name", "audit")->firstOrFail();
        $this->assertEquals("purgó el log de actividad", $audit->description);
        $this->assertEquals(1, $audit->getExtraProperty("deleted"));
        $this->assertNotNull($audit->causer_username);
    }

    public function test_purge_preview_does_not_delete(): void
    {
        $this->authenticateWith(self::PURGE_PERMISSIONS);
        $this->makeActivity(["log_name" => "viejo", "created_at" => now()->subDays(400)]);

        $response = $this->getJson("/api/activities/purge/preview")->assertStatus(200);

        $response->assertJsonPath("data.deleted", 1);
        $this->assertEquals(1, Activity::where("log_name", "viejo")->count());
    }
}
