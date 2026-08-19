<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Un estado del ciclo de vida de una entidad de negocio.
 *
 * Agregar un estado nuevo es un INSERT, no un deploy: por eso vive en BD y no en un enum.
 */
class State extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = [
        "entity",
        "key",
        "name",
        "description",
        "color",
        "icon",
        "is_initial",
        "is_final",
        "sort_order",
    ];

    protected function casts(): array
    {
        return [
            "is_initial" => "boolean",
            "is_final" => "boolean",
            "sort_order" => "integer",
        ];
    }

    public function scopeForEntity(Builder $query, string $entity): Builder
    {
        return $query->where("entity", $entity)->orderBy("sort_order");
    }

    /** Transiciones que salen de este estado. */
    public function outgoing(): HasMany
    {
        return $this->hasMany(StateTransition::class, "from_state_id");
    }

    /** Transiciones que entran a este estado. */
    public function incoming(): HasMany
    {
        return $this->hasMany(StateTransition::class, "to_state_id");
    }

    protected function logName(): string
    {
        return "estados";
    }

    protected function activitylogFields(): array
    {
        return ["entity", "key", "name", "is_initial", "is_final", "sort_order"];
    }
}
