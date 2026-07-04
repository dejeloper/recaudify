<?php

namespace App\Http\Requests\Parameter;

use App\Enums\ParameterCast;
use App\Enums\ParameterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParameterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(["cast" => "string"]);
    }

    public function rules(): array
    {
        return [
            "type" => ["required", Rule::enum(ParameterType::class)],
            "key" => ["required", "string", "max:100", Rule::unique("parameters")->where("type", $this->input("type"))],
            "value" => ["nullable", "string"],
            "cast" => ["sometimes", "required", Rule::enum(ParameterCast::class)],
            "description" => ["nullable", "string", "max:255"],
            "is_editable" => ["boolean"],
        ];
    }
}
