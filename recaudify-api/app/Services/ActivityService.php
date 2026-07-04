<?php

namespace App\Services;

use App\Repositories\ActivityRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class ActivityService
{
    public function __construct(private readonly ActivityRepository $repository) {}

    public function getAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $paginator = $this->repository->paginate($filters, $perPage);

        $this->attachSubjectLabels($paginator->getCollection());

        return $paginator;
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
