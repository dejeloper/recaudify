<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class ActivityService
{
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /**
     * Filtros soportados: log_name, causer_id, model (basename), subject_id.
     */
    public function getAll(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $model = $filters['model'] ?? null;
        $subjectType = $model ? 'App\\Models\\'.$model : null;

        $paginator = Activity::with('causer')
            ->when($filters['log_name'] ?? null, fn ($q, $v) => $q->where('log_name', $v))
            ->when($filters['causer_id'] ?? null, fn ($q, $v) => $q->where('causer_id', $v))
            ->when($subjectType, fn ($q, $v) => $q->where('subject_type', $v))
            ->when($filters['subject_id'] ?? null, fn ($q, $v) => $q->where('subject_id', $v))
            ->orderByDesc('id')
            ->paginate($perPage);

        $this->attachSubjectLabels($paginator->getCollection());

        return $paginator;
    }

    /**
     * Resuelve la etiqueta legible (nombre) del subject de cada actividad.
     * Una consulta por tipo de modelo (incluye eliminados con softDelete).
     */
    private function attachSubjectLabels(Collection $activities): void
    {
        $groups = $activities->whereNotNull('subject_type')->groupBy('subject_type');

        foreach ($groups as $type => $group) {
            if (! class_exists($type)) {
                continue;
            }

            $ids = $group->pluck('subject_id')->unique()->all();
            $query = $type::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $query->withTrashed();
            }

            $labels = $query->whereIn('id', $ids)->pluck('name', 'id');

            foreach ($group as $activity) {
                $activity->subject_label = $labels[$activity->subject_id] ?? null;
            }
        }
    }
}
