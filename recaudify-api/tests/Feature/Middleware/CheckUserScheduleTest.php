<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckUserScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeCollector(): User
    {
        Permission::firstOrCreate(["name" => "parametros.ver", "guard_name" => "api"]);
        $role = Role::firstOrCreate(["name" => "cobrador", "guard_name" => "api"]);
        $role->givePermissionTo("parametros.ver");

        return User::factory()->afterCreating(fn(User $u) => $u->assignRole("cobrador"))->create();
    }

    public function test_superadmin_bypasses_schedule_check(): void
    {
        Permission::firstOrCreate(["name" => "parametros.ver", "guard_name" => "api"]);
        $role = Role::firstOrCreate(["name" => "superadmin", "guard_name" => "api"]);
        $role->givePermissionTo("parametros.ver");
        $user = User::factory()->afterCreating(fn(User $u) => $u->assignRole("superadmin"))->create();

        $this->actingAs($user, "api")->getJson("/api/parameters")->assertStatus(200);
    }

    public function test_user_without_schedule_is_forbidden(): void
    {
        $user = $this->makeCollector();

        $this->actingAs($user, "api")
            ->getJson("/api/parameters")
            ->assertStatus(403)
            ->assertJsonPath("message", "No tiene horario de acceso asignado.");
    }

    public function test_user_within_schedule_is_allowed(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 10:00:00"));
        $user = $this->makeCollector();
        $user->schedules()->create([
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->actingAs($user, "api")->getJson("/api/parameters")->assertStatus(200);
    }

    public function test_user_outside_schedule_is_forbidden(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 18:30:00"));
        $user = $this->makeCollector();
        $user->schedules()->create([
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->actingAs($user, "api")
            ->getJson("/api/parameters")
            ->assertStatus(403)
            ->assertJsonPath("message", "Acceso fuera del horario permitido.");
    }
}
