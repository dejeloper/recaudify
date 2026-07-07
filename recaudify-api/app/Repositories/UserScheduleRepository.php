<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Eloquent\Collection;

class UserScheduleRepository
{
    public function forUser(User $user): Collection
    {
        return $user->schedules()->orderBy("day_of_week")->get();
    }

    public function find(int $id): ?UserSchedule
    {
        return UserSchedule::find($id);
    }

    public function existsForDay(User $user, int $dayOfWeek): bool
    {
        return $user->schedules()->where("day_of_week", $dayOfWeek)->exists();
    }

    public function create(User $user, array $data): UserSchedule
    {
        return $user->schedules()->create($data);
    }
}
