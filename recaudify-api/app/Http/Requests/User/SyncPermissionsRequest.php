<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SyncPermissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'assign' => ['required', 'boolean'],
        ];
    }
}
