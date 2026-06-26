<?php

namespace App\Http\Requests\CallReason;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:30"],
            "color" => ["nullable", "string", "max:30"],
            "active" => ["sometimes", "boolean"],
        ];
    }
}
