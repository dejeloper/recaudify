<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'     => ['sometimes', 'string', 'min:3', 'max:100'],
            'username' => ['sometimes', 'string', 'min:3', 'max:50', 'unique:users,username,' . $id, 'regex:/^[a-zA-Z0-9._-]+$/'],
            'email'    => ['nullable', 'email', 'max:150'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'role'     => ['nullable', 'string', 'exists:roles,name'],
            'active'   => ['sometimes', 'boolean'],
        ];
    }
}
