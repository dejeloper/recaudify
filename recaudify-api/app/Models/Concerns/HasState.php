<?php

namespace App\Models\Concerns;

use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Da ciclo de vida a una entidad de negocio.
 *
 * El modelo declara a qué entidad del catálogo de estados pertenece; el resto lo resuelve
 * `StateMachine`. Requiere una columna `state_id`.
 */
trait HasState
{
    /** Clave de entidad en la tabla `states` (ej. "contract"). */
    abstract public function stateEntity(): string;

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, "state_id");
    }

    /** Clave del estado actual, o null si todavía no tiene. */
    public function stateKey(): ?string
    {
        return $this->state?->key;
    }

    public function isInState(string ...$keys): bool
    {
        return in_array($this->stateKey(), $keys, true);
    }

    /** Un registro en estado final ya no se mueve más. */
    public function isFinalState(): bool
    {
        return (bool) $this->state?->is_final;
    }

    public function scopeInState(Builder $query, string ...$keys): Builder
    {
        return $query->whereHas("state", fn(Builder $q) => $q->whereIn("key", $keys));
    }
}
