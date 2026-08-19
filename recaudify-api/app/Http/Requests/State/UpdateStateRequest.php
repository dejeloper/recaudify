<?php

namespace App\Http\Requests\State;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStateRequest extends FormRequest
{
    public function rules(): array
    {
        $state = $this->route("id");

        return [
            // La entidad y la clave no se cambian: hay registros apuntando a este estado.
            "name" => ["sometimes", "required", "string", "max:100"],
            "description" => ["nullable", "string", "max:255"],
            "color" => ["nullable", "string", "max:20"],
            "icon" => ["nullable", "string", "max:50"],
            "is_initial" => ["boolean"],
            "is_final" => ["boolean"],
            "sort_order" => ["integer", "min:0"],
            "key" => [
                "sometimes",
                "string",
                "max:50",
                "regex:/^[a-z][a-z0-9_]*$/",
                Rule::unique("states", "key")
                    ->where("entity", $this->input("entity"))
                    ->whereNull("deleted_at")
                    ->ignore($state),
            ],
        ];
    }
}
