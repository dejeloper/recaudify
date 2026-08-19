<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $causer = $activity->causer ?? Auth::user();

            if (!($causer instanceof User)) {
                return;
            }

            $activity->causer_username ??= $causer->username;
            $activity->causer_name ??= $causer->name;
        });
    }

    /** Nombre del autor, priorizando siempre el snapshot sobre la relación viva. */
    public function causerName(): ?string
    {
        return $this->causer_name ?? $this->causer?->name;
    }

    /** Usuario del autor, priorizando siempre el snapshot sobre la relación viva. */
    public function causerUsername(): ?string
    {
        return $this->causer_username ?? $this->causer?->username;
    }
}
