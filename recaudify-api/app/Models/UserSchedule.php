<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSchedule extends Model
{
    protected $fillable = ['user_id', 'day_of_week', 'start_time', 'end_time', 'show_status'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'show_status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
