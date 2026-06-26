<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallReason extends Model
{
    use LogsCatalogActivity, SoftDeletes;

    protected $fillable = ["name", "color", "active"];

    protected $attributes = [
        "active" => true,
    ];

    protected $casts = [
        "active" => "boolean",
    ];

    protected function activitylogFields(): array
    {
        return ["name", "color", "active"];
    }
}
