<?php

namespace App\Http\Requests\Rate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'value' => ['required', 'integer', 'min:0'],
            'installments' => ['required', 'integer', 'min:0'],
            'installment_value' => ['required', 'integer', 'min:0'],
            'discount' => ['sometimes', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
