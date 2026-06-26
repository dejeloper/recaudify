<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["name", "value", "active"];

    protected $attributes = [
        "active" => true,
    ];

    protected $casts = [
        "value" => "integer",
        "active" => "boolean",
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    protected function logName(): string
    {
        return "catalogos";
    }

    protected function activitylogFields(): array
    {
        return ["name", "value", "active"];
    }
}
