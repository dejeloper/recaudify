<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAudit extends Model
{
    protected $fillable = [
        "user_id",
        "username",
        "status",
        "reason",
        "ip_address",
        "user_agent",
        "os_name",
        "os_version",
        "device_type",
        "latitude",
        "longitude",
        "accuracy",
        "logged_at",
    ];

    protected $casts = [
        "latitude" => "float",
        "longitude" => "float",
        "accuracy" => "float",
        "logged_at" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
