<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CallReason extends Model
{
    use SoftDeletes;

    protected $fillable = ["name", "color", "active"];

    protected $casts = [
        "active" => "boolean",
    ];
}
