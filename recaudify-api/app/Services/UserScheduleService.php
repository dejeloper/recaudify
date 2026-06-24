<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Eloquent\Collection;

class UserScheduleService
{
    public function getForUser(User $user): Collection
    {
        return $user->schedules()->orderBy('day_of_week')->get();
    }

    public function isDuplicateDay(User $user, int $dayOfWeek): bool
    {
        return $user->schedules()->where('day_of_week', $dayOfWeek)->exists();
    }

    public function create(User $user, array $data): UserSchedule
    {
        return $user->schedules()->create($data);
    }

    public function update(UserSchedule $schedule, array $data): void
    {
        $schedule->update($data);
    }

    public function delete(UserSchedule $schedule): void
    {
        $schedule->delete();
    }
}
