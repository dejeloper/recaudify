<?php

namespace Tests\Feature\Schedule;

use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserScheduleTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["schedules.view", "schedules.create", "schedules.edit", "schedules.delete"];

    public function test_index_lists_schedules_for_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $target = User::factory()->create();
        UserSchedule::create([
            "user_id" => $target->id,
            "day_of_week" => 1,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->getJson("/api/users/{$target->id}/schedules")
            ->assertStatus(200)
            ->assertJsonCount(1, "data");
    }

    public function test_store_creates_schedule(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $target = User::factory()->create();

        $this->postJson("/api/users/{$target->id}/schedules", [
            "day_of_week" => 1,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ])->assertStatus(201);

        $this->assertDatabaseHas("user_schedules", ["user_id" => $target->id, "day_of_week" => 1]);
    }

    public function test_store_rejects_duplicate_day_with_409(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $target = User::factory()->create();
        UserSchedule::create([
            "user_id" => $target->id,
            "day_of_week" => 1,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->postJson("/api/users/{$target->id}/schedules", [
            "day_of_week" => 1,
            "start_time" => "09:00",
            "end_time" => "18:00",
        ])->assertStatus(409);
    }

    public function test_store_validates_time_format_and_order(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $target = User::factory()->create();

        // end_time antes de start_time + day_of_week fuera de rango.
        $this->postJson("/api/users/{$target->id}/schedules", [
            "day_of_week" => 9,
            "start_time" => "17:00",
            "end_time" => "08:00",
        ])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["day_of_week", "end_time"]]);
    }

    public function test_update_and_destroy_schedule(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $target = User::factory()->create();
        $schedule = UserSchedule::create([
            "user_id" => $target->id,
            "day_of_week" => 1,
            "start_time" => "08:00",
            "end_time" => "17:00",
        ]);

        $this->putJson("/api/schedules/{$schedule->id}", ["end_time" => "18:00"])->assertStatus(200);
        $this->assertDatabaseHas("user_schedules", ["id" => $schedule->id, "end_time" => "18:00"]);

        $this->deleteJson("/api/schedules/{$schedule->id}")->assertStatus(200);
        $this->assertDatabaseMissing("user_schedules", ["id" => $schedule->id]);
    }

    public function test_requires_authentication(): void
    {
        $target = User::factory()->create();
        $this->getJson("/api/users/{$target->id}/schedules")->assertStatus(401);
    }
}
