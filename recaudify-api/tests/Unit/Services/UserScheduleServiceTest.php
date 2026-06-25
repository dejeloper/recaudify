<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserScheduleService();
    }

    public function test_is_duplicate_day_detects_existing_day(): void
    {
        $user = User::factory()->create();
        $user->schedules()->create(["day_of_week" => 1, "start_time" => "08:00", "end_time" => "17:00"]);

        $this->assertTrue($this->service->isDuplicateDay($user, 1));
        $this->assertFalse($this->service->isDuplicateDay($user, 2));
    }

    public function test_get_for_user_returns_schedules_ordered_by_day(): void
    {
        $user = User::factory()->create();
        $user->schedules()->create(["day_of_week" => 3, "start_time" => "08:00", "end_time" => "17:00"]);
        $user->schedules()->create(["day_of_week" => 1, "start_time" => "08:00", "end_time" => "17:00"]);

        $days = $this->service->getForUser($user)->pluck("day_of_week")->all();

        $this->assertSame([1, 3], $days);
    }

    public function test_create_update_and_delete(): void
    {
        $user = User::factory()->create();

        $schedule = $this->service->create($user, [
            "day_of_week" => 2,
            "start_time" => "09:00",
            "end_time" => "18:00",
        ]);
        $this->assertDatabaseHas("user_schedules", ["id" => $schedule->id, "day_of_week" => 2]);

        $this->service->update($schedule, ["end_time" => "20:00"]);
        $this->assertSame("20:00", substr($schedule->fresh()->end_time, 0, 5));

        $this->service->delete($schedule);
        $this->assertDatabaseMissing("user_schedules", ["id" => $schedule->id]);
    }
}
