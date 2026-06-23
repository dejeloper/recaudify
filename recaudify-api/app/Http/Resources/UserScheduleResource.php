<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserScheduleResource extends JsonResource
{
    private const DAY_NAMES = [
        'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'day_of_week' => $this->day_of_week,
            'day_name'    => self::DAY_NAMES[$this->day_of_week],
            'start_time'  => substr($this->start_time, 0, 5),
            'end_time'    => substr($this->end_time, 0, 5),
        ];
    }
}
