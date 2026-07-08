<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        "user_id",
        "session_id",
        "ip_address",
        "user_agent",
        "os_name",
        "os_version",
        "device_type",
        "last_used_at",
        "expires_at",
        "revoked_at",
    ];

    protected $casts = [
        "last_used_at" => "datetime",
        "expires_at" => "datetime",
        "revoked_at" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull("revoked_at")->where("expires_at", ">", now());
    }
}
