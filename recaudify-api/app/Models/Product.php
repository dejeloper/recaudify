<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ["name", "value", "active"];

    protected $casts = [
        "value" => "integer",
        "active" => "boolean",
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
