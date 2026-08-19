<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateTransitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "entity" => $this->entity,
            "from" => $this->fromState
                ? ["id" => $this->fromState->id, "key" => $this->fromState->key, "name" => $this->fromState->name]
                : null,
            "to" => $this->toState
                ? ["id" => $this->toState->id, "key" => $this->toState->key, "name" => $this->toState->name]
                : null,
            "permission" => $this->permission,
            "is_automatic" => $this->is_automatic,
            "requires_authorization" => $this->requires_authorization,
            "requires_reason" => $this->requires_reason,
            "label" => $this->label,
            "is_creation" => $this->from_state_id === null,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
