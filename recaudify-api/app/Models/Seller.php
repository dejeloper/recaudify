<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seller extends Model
{
    use LogsCatalogActivity, SoftDeletes;

    protected $fillable = ["name", "username", "active"];

    protected $attributes = [
        "active" => true,
    ];

    protected $casts = [
        "active" => "boolean",
    ];

    protected function activitylogFields(): array
    {
        return ["name", "username", "active"];
    }
}
