<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un movimiento permitido entre dos estados.
 *
 * Lo que no está en esta tabla no se puede hacer: el motor rechaza cualquier cambio de estado que
 * no tenga su transición declarada.
 */
class StateTransition extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = [
        "entity",
        "from_state_id",
        "to_state_id",
        "permission",
        "is_automatic",
        "requires_authorization",
        "requires_reason",
        "label",
    ];

    protected function casts(): array
    {
        return [
            "is_automatic" => "boolean",
            "requires_authorization" => "boolean",
            "requires_reason" => "boolean",
        ];
    }

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(State::class, "from_state_id");
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(State::class, "to_state_id");
    }

    /** Transición de creación: el registro nace en to_state, no viene de ningún estado previo. */
    public function isCreation(): bool
    {
        return $this->from_state_id === null;
    }

    protected function logName(): string
    {
        return "estados";
    }

    protected function activitylogFields(): array
    {
        return [
            "entity",
            "from_state_id",
            "to_state_id",
            "permission",
            "is_automatic",
            "requires_authorization",
            "requires_reason",
        ];
    }
}
