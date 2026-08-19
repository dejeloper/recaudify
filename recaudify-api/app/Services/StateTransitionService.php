<?php

namespace App\Services;

use App\Models\State;
use App\Models\StateTransition;
use App\Repositories\StateTransitionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Administración de las transiciones permitidas.
 *
 * Cuida que el grafo no quede inconsistente: sin duplicados, sin saltos entre entidades distintas,
 * sin salidas desde un estado final.
 */
class StateTransitionService
{
    public function __construct(private readonly StateTransitionRepository $repository) {}

    public function all(?string $entity = null): Collection
    {
        return $this->repository->all($entity);
    }

    public function find(int $id): ?StateTransition
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?StateTransition
    {
        return $this->repository->findTrashed($id);
    }

    public function trashed(?string $entity = null): Collection
    {
        return $this->repository->trashed($entity);
    }

    public function create(array $data): StateTransition
    {
        $this->assertValid($data);

        return $this->repository->create($data)->load(["fromState", "toState"]);
    }

    public function update(StateTransition $transition, array $data): StateTransition
    {
        $this->assertValid(
            array_merge($transition->only(["entity", "from_state_id", "to_state_id"]), $data),
            $transition->id,
        );

        $transition->update($data);

        return $transition->fresh(["fromState", "toState"]);
    }

    public function delete(StateTransition $transition): void
    {
        $transition->delete();
    }

    public function restore(StateTransition $transition): StateTransition
    {
        $transition->restore();

        return $transition;
    }

    private function assertValid(array $data, ?int $ignoreId = null): void
    {
        $entity = $data["entity"];
        $fromId = $data["from_state_id"] ?? null;
        $toId = $data["to_state_id"];

        if ($fromId !== null && (int) $fromId === (int) $toId) {
            throw ValidationException::withMessages(["to_state_id" => "Una transición no puede ir a su mismo estado."]);
        }

        $this->assertBelongsToEntity($fromId, $entity, "from_state_id");
        $this->assertBelongsToEntity($toId, $entity, "to_state_id");
        $this->assertNotDuplicated($entity, $fromId, (int) $toId, $ignoreId);
        $this->assertOriginIsNotFinal($fromId);
    }

    private function assertBelongsToEntity(?int $stateId, string $entity, string $field): void
    {
        if ($stateId === null) {
            return;
        }

        $state = State::find($stateId);

        if ($state && $state->entity === $entity) {
            return;
        }

        throw ValidationException::withMessages([
            $field => "El estado seleccionado no pertenece a la entidad {$entity}.",
        ]);
    }

    private function assertNotDuplicated(string $entity, ?int $fromId, int $toId, ?int $ignoreId): void
    {
        if (!$this->repository->existsFor($entity, $fromId, $toId, $ignoreId)) {
            return;
        }

        throw ValidationException::withMessages(["to_state_id" => "Esa transición ya existe."]);
    }

    private function assertOriginIsNotFinal(?int $fromId): void
    {
        if ($fromId === null) {
            return;
        }

        $from = State::find($fromId);

        if (!$from?->is_final) {
            return;
        }

        throw ValidationException::withMessages([
            "from_state_id" => "Un estado final no puede tener transiciones de salida.",
        ]);
    }
}
