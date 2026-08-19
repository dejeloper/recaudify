<?php

namespace App\Services;

use App\Exceptions\StateTransitionException;
use App\Models\State;
use App\Models\StateTransition;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Motor de ciclo de vida.
 *
 * Lo que no está declarado en `state_transitions` no ocurre. Todo cambio de estado pasa por acá,
 * queda dentro de una transacción y deja registro en auditoría con su motivo.
 */
class StateMachine
{
    /** Estado con el que nace un registro de la entidad. */
    public function initialState(string $entity): State
    {
        $state = State::query()->where("entity", $entity)->where("is_initial", true)->first();

        return $state ?? throw StateTransitionException::missingInitialState($entity);
    }

    public function findState(string $entity, string $key): State
    {
        $state = State::query()->where("entity", $entity)->where("key", $key)->first();

        return $state ?? throw StateTransitionException::unknownState($entity, $key);
    }

    /**
     * Transiciones disponibles desde el estado actual del registro.
     *
     * Excluye las automáticas y, si se pasa un usuario, las que ese usuario no puede ejecutar. Sirve
     * para dibujar los botones: la UI no debe ofrecer lo que el motor va a rechazar.
     */
    public function availableTransitions(Model $subject, ?User $user = null): Collection
    {
        $transitions = StateTransition::query()
            ->with("toState")
            ->where("entity", $subject->stateEntity())
            ->where("from_state_id", $subject->state_id)
            ->where("is_automatic", false)
            ->get();

        if (!$user) {
            return $transitions;
        }

        return $transitions->filter(fn(StateTransition $t) => $this->userMay($t, $user))->values();
    }

    public function can(Model $subject, string $toKey, ?User $user = null): bool
    {
        $transition = $this->findTransition($subject, $toKey);

        if (!$transition || $transition->is_automatic) {
            return false;
        }

        return !$user || $this->userMay($transition, $user);
    }

    /**
     * Ejecuta el cambio de estado.
     *
     * @param  bool  $automatic  true cuando lo dispara el motor (job de mora), no una persona.
     * @param  bool  $authorized true cuando el módulo de autorizaciones ya aprobó la solicitud.
     */
    public function apply(
        Model $subject,
        string $toKey,
        ?string $reason = null,
        ?User $user = null,
        bool $automatic = false,
        bool $authorized = false,
    ): Model {
        $entity = $subject->stateEntity();
        $user = $user ?? Auth::user();

        $transition = $this->findTransition($subject, $toKey);

        if (!$transition) {
            throw StateTransitionException::notAllowed($entity, $subject->stateKey(), $toKey);
        }

        $this->assertAllowed($transition, $toKey, $reason, $user, $automatic, $authorized);

        $from = $subject->state;

        // El cambio de estado y su registro son un solo hecho: si falla el log, no cambia el estado.
        return DB::transaction(function () use ($subject, $transition, $from, $reason, $user, $automatic) {
            $subject->state_id = $transition->to_state_id;
            $subject->save();

            activity("estados")
                ->causedBy($user)
                ->performedOn($subject)
                ->event("state_changed")
                ->withProperties([
                    "old" => ["state" => $from?->key],
                    "attributes" => ["state" => $transition->toState->key],
                    "reason" => $reason,
                    "automatic" => $automatic,
                ])
                ->log("cambió el estado");

            return $subject->refresh();
        });
    }

    private function findTransition(Model $subject, string $toKey): ?StateTransition
    {
        $to = $this->findState($subject->stateEntity(), $toKey);

        return StateTransition::query()
            ->with("toState")
            ->where("entity", $subject->stateEntity())
            ->where("from_state_id", $subject->state_id)
            ->where("to_state_id", $to->id)
            ->first();
    }

    private function assertAllowed(
        StateTransition $transition,
        string $toKey,
        ?string $reason,
        ?User $user,
        bool $automatic,
        bool $authorized,
    ): void {
        if ($transition->is_automatic && !$automatic) {
            throw StateTransitionException::automaticOnly($toKey);
        }

        if ($transition->requires_reason && trim((string) $reason) === "") {
            throw StateTransitionException::reasonRequired($toKey);
        }

        if ($transition->requires_authorization && !$authorized) {
            throw StateTransitionException::authorizationRequired($toKey);
        }

        // El motor no pide permisos: no actúa en nombre de nadie.
        if ($automatic) {
            return;
        }

        if ($transition->permission && $user && !$this->userMay($transition, $user)) {
            throw StateTransitionException::forbidden($toKey, $transition->permission);
        }
    }

    private function userMay(StateTransition $transition, User $user): bool
    {
        return !$transition->permission || $user->can($transition->permission);
    }
}
