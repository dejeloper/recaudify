<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:100", "unique:permissions,name", 'regex:/^[a-z_]+\.[a-z_-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            "name.regex" => "El permiso debe tener el formato modulo.accion (ej. clientes.crear).",
        ];
    }
}
