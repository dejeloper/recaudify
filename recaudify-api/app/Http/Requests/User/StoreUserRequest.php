<?php

namespace App\Http\Requests\User;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PasswordPolicyService $passwordPolicy): array
    {
        return [
            "name" => ["required", "string", "min:3", "max:100"],
            "username" => ["required", "string", "min:3", "max:50", "unique:users,username", 'regex:/^[a-z0-9._-]+$/'],
            "email" => ["nullable", "email", "max:150"],
            "password" => ["required", "string", $passwordPolicy->rule(), "confirmed"],
            "role" => ["nullable", "string", "exists:roles,name"],
            "active" => ["boolean"],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(["username" => strtolower(trim($this->username ?? ""))]);
    }
}
