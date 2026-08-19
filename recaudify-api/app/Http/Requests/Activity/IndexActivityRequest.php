<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "log_name" => ["nullable", "string", "max:255"],
            "causer_id" => ["nullable", "integer"],
            "user" => ["nullable", "string", "max:255"],
            "model" => ["nullable", "string", "max:255"],
            "subject_id" => ["nullable", "integer"],
            "from" => ["nullable", "date"],
            "to" => ["nullable", "date", "after_or_equal:from"],
            "per_page" => ["nullable", "integer", "min:1"],
        ];
    }

    public function messages(): array
    {
        return [
            "to.after_or_equal" => "La fecha final no puede ser anterior a la inicial.",
        ];
    }
}
