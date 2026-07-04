<?php

namespace App\Services;

use App\Repositories\ActivityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class ActivityService
{
    public function __construct(
        private readonly ActivityRepository $repository,
        private readonly UserService $userService,
    ) {}

    public function getAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $paginator = $this->repository->paginate($this->resolveUserFilter($filters), $perPage);

        $this->attachSubjectLabels(new Collection($paginator->items()));

        return $paginator;
    }

    private function resolveUserFilter(array $filters): array
    {
        $user = $filters["user"] ?? null;
        unset($filters["user"]);

        if (!$user) {
            return $filters;
        }

        if (strtolower($user) === "sistema") {
            $filters["causer_is_null"] = true;

            return $filters;
        }

        $filters["causer_id"] = $this->userService->findByUsername($user)?->id ?? -1;

        return $filters;
    }

    private function attachSubjectLabels(Collection $activities): void
    {
        $groups = $activities->whereNotNull("subject_type")->groupBy("subject_type");

        foreach ($groups as $type => $group) {
            if (!class_exists($type)) {
                continue;
            }

            $model = new $type();

            if (!Schema::hasColumn($model->getTable(), "name")) {
                continue;
            }

            $ids = $group->pluck("subject_id")->unique()->all();
            $query = $type::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $query->withTrashed();
            }

            $labels = $query->whereIn("id", $ids)->pluck("name", "id");

            foreach ($group as $activity) {
                $activity->subject_label = $labels[$activity->subject_id] ?? null;
            }
        }
    }
}
