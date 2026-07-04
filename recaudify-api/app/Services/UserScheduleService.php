<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSchedule;
use App\Repositories\UserScheduleRepository;
use Illuminate\Database\Eloquent\Collection;

class UserScheduleService
{
    public function __construct(private readonly UserScheduleRepository $repository) {}

    public function getForUser(User $user): Collection
    {
        return $this->repository->forUser($user);
    }

    public function find(int $id): ?UserSchedule
    {
        return $this->repository->find($id);
    }

    public function isDuplicateDay(User $user, int $dayOfWeek): bool
    {
        return $this->repository->existsForDay($user, $dayOfWeek);
    }

    public function create(User $user, array $data): UserSchedule
    {
        return $this->repository->create($user, $data);
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
