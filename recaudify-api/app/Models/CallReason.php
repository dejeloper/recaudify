<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallReason extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["name", "color", "active"];

    protected $attributes = [
        "active" => true,
    ];

    protected $casts = [
        "active" => "boolean",
    ];

    protected function logName(): string
    {
        return "catalogos";
    }

    protected function activitylogFields(): array
    {
        return ["name", "color", "active"];
    }
}
