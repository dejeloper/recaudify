<?php

namespace App\Http\Requests\Parameter;

use App\Models\Parameter;
use App\Support\ParameterRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateParameterRequest extends FormRequest
{
    public function rules(): array
    {
        $parameter = $this->parameter();

        return [
            "value" => array_merge(
                ["required", "string"],
                $parameter ? ParameterRules::for($parameter->key, $parameter->cast) : [],
            ),
        ];
    }

    public function messages(): array
    {
        $parameter = $this->parameter();
        $message = $parameter ? ParameterRules::messageFor($parameter->key) : null;

        if (!$message) {
            return [];
        }

        // El mismo mensaje para cualquier regla que falle: al usuario le sirve saber qué se espera,
        // no cuál de las reglas internas no se cumplió.
        return array_fill_keys(["value.in", "value.regex", "value.timezone", "value.size", "value.alpha"], $message);
    }

    private function parameter(): ?Parameter
    {
        return Parameter::find($this->route("id"));
    }
}
