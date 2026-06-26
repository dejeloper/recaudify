<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:100"],
            "value" => ["required", "integer", "min:0"],
            "active" => ["sometimes", "boolean"],
        ];
    }
}
