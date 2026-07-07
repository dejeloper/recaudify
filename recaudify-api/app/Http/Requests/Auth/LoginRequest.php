<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "username" => ["required", "string"],
            "password" => ["required", "string"],
            "latitude" => ["nullable", "numeric", "between:-90,90"],
            "longitude" => ["nullable", "numeric", "between:-180,180"],
            "accuracy" => ["nullable", "numeric", "min:0"],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(["username" => strtolower(trim($this->username ?? ""))]);
    }
}
