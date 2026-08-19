<?php

namespace App\Http\Requests\State;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStateTransitionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "permission" => ["nullable", "string", "exists:permissions,name"],
            "is_automatic" => ["boolean"],
            "requires_authorization" => ["boolean"],
            "requires_reason" => ["boolean"],
            "label" => ["nullable", "string", "max:100"],
        ];
    }
}
