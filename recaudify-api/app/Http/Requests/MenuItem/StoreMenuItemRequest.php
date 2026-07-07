<?php

namespace App\Http\Requests\MenuItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "parent_id" => ["nullable", "integer", "exists:menu_items,id"],
            "label" => ["required", "string", "max:100"],
            "icons" => ["nullable", "array"],
            "icons.*" => ["string"],
            "route" => ["nullable", "string", "max:255"],
            "permission" => ["nullable", "string", "exists:permissions,name"],
            "order" => ["integer", "min:0"],
            "is_active" => ["boolean"],
        ];
    }
}
