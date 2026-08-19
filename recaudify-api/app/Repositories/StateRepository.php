<?php

namespace App\Repositories;

use App\Models\State;
use Illuminate\Database\Eloquent\Collection;

class StateRepository
{
    public function all(?string $entity = null): Collection
    {
        return State::query()
            ->when($entity, fn($query, $value) => $query->where("entity", $value))
            ->orderBy("entity")
            ->orderBy("sort_order")
            ->get();
    }

    public function find(int $id): ?State
    {
        return State::find($id);
    }

    public function findTrashed(int $id): ?State
    {
        return State::onlyTrashed()->find($id);
    }

    public function trashed(?string $entity = null): Collection
    {
        return State::onlyTrashed()
            ->when($entity, fn($query, $value) => $query->where("entity", $value))
            ->orderBy("entity")
            ->orderBy("sort_order")
            ->get();
    }

    public function create(array $data): State
    {
        return State::create($data);
    }

    /** Entidades que hoy tienen ciclo de vida configurado. */
    public function entities(): array
    {
        return State::query()->distinct()->orderBy("entity")->pluck("entity")->all();
    }
}
