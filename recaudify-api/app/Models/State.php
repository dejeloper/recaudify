<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["name", "state_type", "active"];

    protected function logName(): string
    {
        return "catalogos";
    }

    protected $casts = [
        "active" => "boolean",
    ];

    protected function activitylogFields(): array
    {
        return ["name", "state_type", "active"];
    }
}
