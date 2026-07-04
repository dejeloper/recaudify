<?php

namespace App\Http\Requests\Parameter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParameterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "value" => ["required", "string"],
        ];
    }
}
