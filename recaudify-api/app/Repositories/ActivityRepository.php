<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $subjectType = isset($filters["model"]) ? "App\\Models\\" . $filters["model"] : null;

        return Activity::with("causer")
            ->when($filters["log_name"] ?? null, fn($q, $v) => $q->where("log_name", $v))
            ->when($filters["causer_id"] ?? null, fn($q, $v) => $q->where("causer_id", $v))
            ->when($subjectType, fn($q, $v) => $q->where("subject_type", $v))
            ->when($filters["subject_id"] ?? null, fn($q, $v) => $q->where("subject_id", $v))
            ->orderByDesc("id")
            ->paginate($perPage);
    }
}
