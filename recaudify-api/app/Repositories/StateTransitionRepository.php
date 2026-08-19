<?php

namespace App\Repositories;

use App\Models\StateTransition;
use Illuminate\Database\Eloquent\Collection;

class StateTransitionRepository
{
    public function all(?string $entity = null): Collection
    {
        return StateTransition::query()
            ->with(["fromState", "toState"])
            ->when($entity, fn($query, $value) => $query->where("entity", $value))
            ->orderBy("entity")
            ->orderBy("from_state_id")
            ->get();
    }

    public function find(int $id): ?StateTransition
    {
        return StateTransition::with(["fromState", "toState"])->find($id);
    }

    public function findTrashed(int $id): ?StateTransition
    {
        return StateTransition::onlyTrashed()->find($id);
    }

    public function trashed(?string $entity = null): Collection
    {
        return StateTransition::onlyTrashed()
            ->with(["fromState", "toState"])
            ->when($entity, fn($query, $value) => $query->where("entity", $value))
            ->orderBy("entity")
            ->get();
    }

    public function create(array $data): StateTransition
    {
        return StateTransition::create($data);
    }

    public function existsFor(string $entity, ?int $fromStateId, int $toStateId, ?int $ignoreId = null): bool
    {
        return StateTransition::query()
            ->where("entity", $entity)
            ->where("from_state_id", $fromStateId)
            ->where("to_state_id", $toStateId)
            ->when($ignoreId, fn($query, $value) => $query->where("id", "!=", $value))
            ->exists();
    }

    /** Transiciones que tocan un estado, en cualquiera de las dos puntas. */
    public function touchingState(int $stateId): Collection
    {
        return StateTransition::query()->where("from_state_id", $stateId)->orWhere("to_state_id", $stateId)->get();
    }
}
