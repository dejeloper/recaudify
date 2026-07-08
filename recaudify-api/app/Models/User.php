<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, LogsModelActivity, Notifiable, SoftDeletes;

    protected string $guard_name = "api";

    protected $fillable = ["name", "username", "email", "password", "active", "password_changed_at"];

    protected $hidden = ["password"];

    protected function casts(): array
    {
        return [
            "password" => "hashed",
            "active" => "boolean",
            "password_changed_at" => "datetime",
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    protected function logName(): string
    {
        return "usuarios";
    }

    protected function activitylogFields(): array
    {
        return ["name", "username", "email", "active"];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(UserSchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function getJWTCustomClaims(): array
    {
        return [
            "role" => $this->getRoleNames()->first(),
            "aud" => config("app.url"),
        ];
    }
}
