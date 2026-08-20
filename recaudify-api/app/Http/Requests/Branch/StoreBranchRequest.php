<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "code" => [
                "required",
                "string",
                "max:20",
                "regex:/^[A-Z0-9][A-Z0-9-]*$/",
                Rule::unique("branches", "code")->whereNull("deleted_at"),
            ],
            "name" => ["required", "string", "max:100", Rule::unique("branches", "name")->whereNull("deleted_at")],
            "address" => ["nullable", "string", "max:255"],
            "city" => ["nullable", "string", "max:100"],
            "phone" => ["nullable", "string", "max:30"],
            "email" => ["nullable", "email", "max:255"],
            "is_main" => ["boolean"],
            "sort_order" => ["integer", "min:0"],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has("code")) {
            $this->merge(["code" => strtoupper(trim($this->input("code") ?? ""))]);
        }
    }

    public function messages(): array
    {
        return [
            "code.regex" =>
                "El código debe ser mayúsculas, números y guiones, empezando por letra o número (ej. BOG-CEN).",
            "code.unique" => "Ya existe una sucursal con ese código.",
            "name.unique" => "Ya existe una sucursal con ese nombre.",
        ];
    }
}
