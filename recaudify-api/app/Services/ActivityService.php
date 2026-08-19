<?php

namespace App\Services;

use App\Enums\ParameterType;
use App\Repositories\ActivityRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ActivityService
{
    private const RETENTION_KEY = "activity_log_retention_days";

    public function __construct(
        private readonly ActivityRepository $repository,
        private readonly UserService $userService,
        private readonly ParameterService $parameterService,
    ) {}

    public function purge(?int $days = null): array
    {
        $days = $days ?? (int) $this->parameterService->get(ParameterType::Application, self::RETENTION_KEY);
        $cutoff = Carbon::now()->subDays($days)->toDateTimeString();

        $deleted = $this->repository->deleteOlderThan($cutoff);

        activity("audit")
            ->withProperties(["deleted" => $deleted, "cutoff" => $cutoff, "retention_days" => $days])
            ->log("purgó el log de actividad");

        return ["deleted" => $deleted, "cutoff" => $cutoff, "retention_days" => $days];
    }

    public function previewPurge(?int $days = null): array
    {
        $days = $days ?? (int) $this->parameterService->get(ParameterType::Application, self::RETENTION_KEY);
        $cutoff = Carbon::now()->subDays($days)->toDateTimeString();

        return [
            "deleted" => $this->repository->countOlderThan($cutoff),
            "cutoff" => $cutoff,
            "retention_days" => $days,
        ];
    }

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
