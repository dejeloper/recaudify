<?php

namespace Tests\Feature\Middleware;

use App\Enums\ParameterType;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            [
                ["pagination_per_page", "25", "integer", ParameterType::Application],
                ["pagination_max_per_page", "100", "integer", ParameterType::Application],
            ]
            as [$key, $value, $cast, $type]
        ) {
            Parameter::query()->updateOrCreate(
                ["type" => $type->value, "key" => $key],
                ["value" => $value, "cast" => $cast],
            );
        }
    }

    private function setMaintenance(bool $enabled, string $scope = "all", ?string $message = null): void
    {
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Configuration->value, "key" => "maintenance_mode"],
            ["value" => $enabled ? "true" : "false", "cast" => "boolean"],
        );
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Configuration->value, "key" => "maintenance_scope"],
            ["value" => $scope, "cast" => "string"],
        );

        if ($message !== null) {
            Parameter::query()->updateOrCreate(
                ["type" => ParameterType::Configuration->value, "key" => "maintenance_message"],
                ["value" => $message, "cast" => "string"],
            );
        }

        Cache::flush();
    }

    /** Usuario común: sin el permiso de excepción y sin el bypass de superadmin. */
    private function regularUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::firstOrCreate(["name" => "operativo", "guard_name" => "api"]);
        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->withRole("operativo")->create();
        UserSchedule::create([
            "user_id" => $user->id,
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "00:00",
            "end_time" => "23:59",
            "show_status" => true,
        ]);

        $this->actingAs($user, "api");

        return $user;
    }

    public function test_requests_pass_when_maintenance_is_off(): void
    {
        $this->setMaintenance(false);
        $this->regularUser(["audit.view"]);

        $this->getJson("/api/activities")->assertStatus(200);
    }

    public function test_blocks_regular_users_when_active(): void
    {
        $this->setMaintenance(true);
        $this->regularUser(["audit.view"]);

        $response = $this->getJson("/api/activities")->assertStatus(503);

        $response->assertJsonPath("success", false);
        $response->assertJsonPath("data.maintenance", true);
        $response->assertJsonPath("data.scope", CheckMaintenanceMode::SCOPE_ALL);
    }

    public function test_shows_the_configured_message(): void
    {
        $this->setMaintenance(true, message: "Volvemos a las 6 p.m.");
        $this->regularUser(["audit.view"]);

        $this->getJson("/api/activities")->assertStatus(503)->assertJsonPath("message", "Volvemos a las 6 p.m.");
    }

    public function test_users_with_bypass_permission_keep_working(): void
    {
        $this->setMaintenance(true);
        $this->authenticateWith(["audit.view", CheckMaintenanceMode::BYPASS_PERMISSION]);

        $this->getJson("/api/activities")->assertStatus(200);
    }

    public function test_writes_scope_allows_reading(): void
    {
        $this->setMaintenance(true, scope: CheckMaintenanceMode::SCOPE_WRITES);
        $this->regularUser(["audit.view"]);

        $this->getJson("/api/activities")->assertStatus(200);
    }

    public function test_writes_scope_blocks_writing(): void
    {
        $this->setMaintenance(true, scope: CheckMaintenanceMode::SCOPE_WRITES);
        $this->regularUser(["states.view", "states.create"]);

        $response = $this->postJson("/api/states", [
            "entity" => "contract",
            "key" => "nuevo",
            "name" => "Nuevo",
        ])->assertStatus(503);

        $response->assertJsonPath("data.scope", CheckMaintenanceMode::SCOPE_WRITES);
    }

    public function test_health_stays_available(): void
    {
        $this->setMaintenance(true);

        // El monitor externo tiene que poder distinguir "en mantenimiento" de "caído".
        $this->getJson("/api/health")->assertStatus(200);
    }

    public function test_login_stays_available(): void
    {
        $this->setMaintenance(true);

        // 401 y no 503: la ruta responde, solo que las credenciales son inválidas.
        $this->postJson("/api/auth/login", [
            "username" => "inexistente",
            "password" => "loquesea",
        ])->assertStatus(401);
    }

    public function test_scheduled_tasks_are_skipped_during_maintenance(): void
    {
        $event = collect(Schedule::events())->first(
            fn($event) => str_contains($event->command ?? "", "activity:purge"),
        );

        $this->setMaintenance(false);
        $this->assertTrue($event->filtersPass($this->app), "Sin mantenimiento la purga debería correr");

        $this->setMaintenance(true);
        $this->assertFalse($event->filtersPass($this->app), "En mantenimiento la purga no debe correr");
    }
}
