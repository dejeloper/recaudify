<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PasswordPolicyService $passwordPolicy): array
    {
        return [
            "current_password" => ["required", "string"],
            "password" => ["required", "string", $passwordPolicy->rule(), "confirmed"],
        ];
    }
}
