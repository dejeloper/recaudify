<?php

namespace App\Http\Requests\Parameter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "key" => ["required", "string", "max:100", "unique:parameters,key," . $this->route("id")],
            "value" => ["required", "string", "max:255"],
            "description" => ["nullable", "string", "max:255"],
        ];
    }
}
