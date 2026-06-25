<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class ActivityService
{
    private const MAX_RESULTS = 200;

    /**
     * Filtros soportados: log_name, causer_id, model (basename), subject_id.
     */
    public function getAll(array $filters = []): Collection
    {
        $model = $filters["model"] ?? null;
        $subjectType = $model ? "App\\Models\\" . $model : null;

        $activities = Activity::with("causer")
            ->when($filters["log_name"] ?? null, fn($q, $v) => $q->where("log_name", $v))
            ->when($filters["causer_id"] ?? null, fn($q, $v) => $q->where("causer_id", $v))
            ->when($subjectType, fn($q, $v) => $q->where("subject_type", $v))
            ->when($filters["subject_id"] ?? null, fn($q, $v) => $q->where("subject_id", $v))
            ->orderByDesc("id")
            ->limit(self::MAX_RESULTS)
            ->get();

        $this->attachSubjectLabels($activities);

        return $activities;
    }

    /**
     * Resuelve la etiqueta legible (nombre) del subject de cada actividad.
     * Una consulta por tipo de modelo (incluye eliminados con softDelete).
     */
    private function attachSubjectLabels(Collection $activities): void
    {
        $groups = $activities->whereNotNull("subject_type")->groupBy("subject_type");

        foreach ($groups as $type => $group) {
            if (!class_exists($type)) {
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
