<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuthService::class);
        Role::firstOrCreate(["name" => "superadmin", "guard_name" => "api"]);
        Role::firstOrCreate(["name" => "cobrador", "guard_name" => "api"]);
    }

    public function test_superadmin_has_no_schedule_restriction(): void
    {
        $user = User::factory()->withRole("superadmin")->create();

        $this->assertNull($this->service->getScheduleAccessError($user));
    }

    public function test_user_without_schedules_is_blocked(): void
    {
        $user = User::factory()->withRole("cobrador")->create();

        $this->assertSame("No tiene horario de acceso asignado.", $this->service->getScheduleAccessError($user));
    }

    public function test_user_within_window_is_allowed(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 10:00:00"));
        $user = User::factory()->withRole("cobrador")->create();
        $user->schedules()->create([
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->assertNull($this->service->getScheduleAccessError($user));
    }

    public function test_user_outside_window_is_blocked(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 18:30:00"));
        $user = User::factory()->withRole("cobrador")->create();
        $user->schedules()->create([
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->assertSame("Acceso fuera del horario permitido.", $this->service->getScheduleAccessError($user));
    }

    public function test_user_with_schedule_on_another_day_is_blocked(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 10:00:00"));
        $user = User::factory()->withRole("cobrador")->create();
        $user->schedules()->create([
            "day_of_week" => (now()->dayOfWeek + 1) % 7, // otro día
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->assertSame("Acceso fuera del horario permitido.", $this->service->getScheduleAccessError($user));
    }

    public function test_current_shift_for_superadmin_is_open(): void
    {
        $user = User::factory()->withRole("superadmin")->create();

        $shift = $this->service->getCurrentShift($user);

        $this->assertTrue($shift["is_within_schedule"]);
        $this->assertTrue($shift["show_status"]);
        $this->assertNull($shift["start_time"]);
        $this->assertNull($shift["end_time"]);
    }

    public function test_current_shift_within_window_returns_times_and_remaining(): void
    {
        $this->travelTo(Carbon::parse("2026-06-24 10:00:00"));
        $user = User::factory()->withRole("cobrador")->create();
        $user->schedules()->create([
            "day_of_week" => now()->dayOfWeek,
            "start_time" => "08:00",
            "end_time" => "17:00",
            "show_status" => true,
        ]);

        $shift = $this->service->getCurrentShift($user);

        $this->assertTrue($shift["is_within_schedule"]);
        $this->assertSame("08:00", $shift["start_time"]);
        $this->assertSame("17:00", $shift["end_time"]);
        $this->assertSame(7 * 60, $shift["remaining_minutes"]); // 10:00 → 17:00
    }
}
