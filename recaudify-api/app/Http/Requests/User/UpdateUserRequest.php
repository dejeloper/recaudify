<?php

namespace App\Http\Requests\User;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PasswordPolicyService $passwordPolicy): array
    {
        $id = $this->route("id");

        return [
            "name" => ["sometimes", "string", "min:3", "max:100"],
            "username" => [
                "sometimes",
                "string",
                "min:3",
                "max:50",
                "unique:users,username," . $id,
                'regex:/^[a-z0-9._-]+$/',
            ],
            "email" => ["nullable", "email", "max:150"],
            "password" => ["sometimes", "nullable", "string", $passwordPolicy->rule(), "confirmed"],
            "role" => ["nullable", "string", "exists:roles,name"],
            "active" => ["sometimes", "boolean"],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has("username")) {
            $this->merge(["username" => strtolower(trim($this->username ?? ""))]);
        }
    }
}
