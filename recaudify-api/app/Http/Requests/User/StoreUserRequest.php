<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email'    => ['nullable', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['nullable', 'string', 'exists:roles,name'],
            'active'   => ['boolean'],
        ];
    }
}
