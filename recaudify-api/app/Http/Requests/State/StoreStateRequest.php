<?php

namespace App\Http\Requests\State;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "entity" => ["required", "string", "max:50"],
            "key" => [
                "required",
                "string",
                "max:50",
                "regex:/^[a-z][a-z0-9_]*$/",
                Rule::unique("states", "key")->where("entity", $this->input("entity"))->whereNull("deleted_at"),
            ],
            "name" => ["required", "string", "max:100"],
            "description" => ["nullable", "string", "max:255"],
            "color" => ["nullable", "string", "max:20"],
            "icon" => ["nullable", "string", "max:50"],
            "is_initial" => ["boolean"],
            "is_final" => ["boolean"],
            "sort_order" => ["integer", "min:0"],
        ];
    }

    public function messages(): array
    {
        return [
            "key.regex" =>
                "La clave debe ser minúsculas, números y guion bajo, empezando por letra (ej. pending_validation).",
            "key.unique" => "Esa clave ya existe para esta entidad.",
        ];
    }
}
