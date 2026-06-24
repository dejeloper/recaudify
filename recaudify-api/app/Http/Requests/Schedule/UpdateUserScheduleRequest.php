<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $endTimeRules = ["sometimes", "date_format:H:i"];

        if ($this->has("start_time")) {
            $endTimeRules[] = "after:start_time";
        }

        return [
            "day_of_week" => ["sometimes", "integer", "min:0", "max:6"],
            "start_time" => ["sometimes", "date_format:H:i"],
            "end_time" => $endTimeRules,
            "show_status" => ["sometimes", "boolean"],
        ];
    }

    public function messages(): array
    {
        return [
            "end_time.after" => "La hora de fin debe ser posterior a la hora de inicio.",
        ];
    }
}
