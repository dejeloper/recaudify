<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["sometimes", "string", "max:100", "unique:roles,name," . $this->route("id")],
            "permissions" => ["array"],
            "permissions.*" => ["string", "exists:permissions,name"],
        ];
    }
}
