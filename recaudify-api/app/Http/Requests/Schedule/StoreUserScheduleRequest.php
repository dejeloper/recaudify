<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('userId');

        return [
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'show_status' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.min'      => 'El día debe ser entre 0 (domingo) y 6 (sábado).',
            'day_of_week.max'      => 'El día debe ser entre 0 (domingo) y 6 (sábado).',
            'end_time.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
