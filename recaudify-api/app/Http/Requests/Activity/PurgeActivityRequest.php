<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class PurgeActivityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Si no se envía, se usa el parámetro activity_log_retention_days.
            "days" => ["nullable", "integer", "min:1"],
        ];
    }
}
