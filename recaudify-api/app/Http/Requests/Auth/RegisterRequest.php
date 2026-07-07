<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PasswordPolicyService $passwordPolicy): array
    {
        return [
            "name" => ["required", "string", "max:100"],
            "username" => ["required", "string", "max:50", "unique:users,username", 'regex:/^[a-z0-9._-]+$/'],
            "email" => ["nullable", "email", "max:150"],
            "password" => ["required", "string", $passwordPolicy->rule(), "confirmed"],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(["username" => strtolower(trim($this->username ?? ""))]);
    }
}
