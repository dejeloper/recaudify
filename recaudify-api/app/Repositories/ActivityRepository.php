<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $subjectType = isset($filters["model"]) ? "App\\Models\\" . $filters["model"] : null;

        return Activity::with("causer")
            ->when($filters["log_name"] ?? null, fn($q, $v) => $q->where("log_name", $v))
            ->when($filters["causer_is_null"] ?? false, fn($q) => $q->whereNull("causer_id"))
            ->when(
                !($filters["causer_is_null"] ?? false) && ($filters["causer_id"] ?? null),
                fn($q) => $q->where("causer_id", $filters["causer_id"]),
            )
            ->when($subjectType, fn($q, $v) => $q->where("subject_type", $v))
            ->when($filters["subject_id"] ?? null, fn($q, $v) => $q->where("subject_id", $v))
            ->orderByDesc("id")
            ->paginate($perPage);
    }
}
