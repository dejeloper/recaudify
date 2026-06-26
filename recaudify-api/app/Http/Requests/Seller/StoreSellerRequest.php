<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:100"],
            "username" => ["nullable", "string", "max:30"],
            "active" => ["sometimes", "boolean"],
        ];
    }
}
