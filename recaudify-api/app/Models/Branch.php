<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["code", "name", "address", "city", "phone", "email", "is_main", "sort_order"];

    protected function casts(): array
    {
        return [
            "is_main" => "boolean",
            "sort_order" => "integer",
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy("sort_order")->orderBy("name");
    }

    protected function logName(): string
    {
        return "sucursales";
    }

    protected function activitylogFields(): array
    {
        return ["code", "name", "address", "city", "phone", "email", "is_main", "sort_order"];
    }
}
