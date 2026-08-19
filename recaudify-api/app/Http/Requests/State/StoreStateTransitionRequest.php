<?php

namespace App\Http\Requests\State;

use Illuminate\Foundation\Http\FormRequest;

class StoreStateTransitionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "entity" => ["required", "string", "max:50"],
            // Null = transición de creación: el registro nace en to_state.
            "from_state_id" => ["nullable", "integer", "exists:states,id"],
            "to_state_id" => ["required", "integer", "exists:states,id"],
            "permission" => ["nullable", "string", "exists:permissions,name"],
            "is_automatic" => ["boolean"],
            "requires_authorization" => ["boolean"],
            "requires_reason" => ["boolean"],
            "label" => ["nullable", "string", "max:100"],
        ];
    }
}
