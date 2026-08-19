<?php

namespace App\Services;

use App\Models\State;
use App\Repositories\StateRepository;
use App\Repositories\StateTransitionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Administración del catálogo de estados.
 *
 * El motor (`StateMachine`) los consume; acá se crean y editan. Las reglas que cuida este servicio
 * son las que evitan dejar un ciclo de vida inconsistente desde la pantalla.
 */
class StateService
{
    public function __construct(
        private readonly StateRepository $repository,
        private readonly StateTransitionRepository $transitions,
    ) {}

    public function all(?string $entity = null): Collection
    {
        return $this->repository->all($entity);
    }

    public function entities(): array
    {
        return $this->repository->entities();
    }

    public function find(int $id): ?State
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?State
    {
        return $this->repository->findTrashed($id);
    }

    public function trashed(?string $entity = null): Collection
    {
        return $this->repository->trashed($entity);
    }

    public function create(array $data): State
    {
        // Marcar uno como inicial implica desmarcar el anterior: son dos escrituras, una sola verdad.
        return DB::transaction(function () use ($data) {
            $state = $this->repository->create($data);

            if ($state->is_initial) {
                $this->demoteOtherInitials($state);
            }

            return $state;
        });
    }

    public function update(State $state, array $data): State
    {
        return DB::transaction(function () use ($state, $data) {
            $state->update($data);

            if ($state->is_initial) {
                $this->demoteOtherInitials($state);
            }

            if ($state->is_final) {
                $this->assertNoOutgoingTransitions($state);
            }

            return $state->fresh();
        });
    }

    public function delete(State $state): void
    {
        $this->assertNotInitial($state);
        $this->assertNotReferenced($state);

        $state->delete();
    }

    public function restore(State $state): State
    {
        $state->restore();

        return $state;
    }

    /** Solo un estado inicial por entidad: es el que decide con qué nace un registro. */
    private function demoteOtherInitials(State $state): void
    {
        State::query()
            ->where("entity", $state->entity)
            ->where("id", "!=", $state->id)
            ->where("is_initial", true)
            ->update(["is_initial" => false]);
    }

    private function assertNotInitial(State $state): void
    {
        if (!$state->is_initial) {
            return;
        }

        throw ValidationException::withMessages([
            "state" => "No se puede eliminar el estado inicial de {$state->entity}. Marque otro como inicial primero.",
        ]);
    }

    private function assertNotReferenced(State $state): void
    {
        $count = $this->transitions->touchingState($state->id)->count();

        if ($count === 0) {
            return;
        }

        throw ValidationException::withMessages([
            "state" => "No se puede eliminar: {$count} transición(es) usan este estado. Elimínelas primero.",
        ]);
    }

    private function assertNoOutgoingTransitions(State $state): void
    {
        if ($state->outgoing()->count() === 0) {
            return;
        }

        throw ValidationException::withMessages([
            "is_final" => "No se puede marcar como final: el estado todavía tiene transiciones de salida.",
        ]);
    }
}
