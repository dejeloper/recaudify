<?php

namespace App\Models;

use App\Enums\ParameterCast;
use App\Enums\ParameterType;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parameter extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["type", "key", "value", "cast", "description", "is_editable"];

    protected function casts(): array
    {
        return [
            "type" => ParameterType::class,
            "cast" => ParameterCast::class,
            "is_editable" => "boolean",
        ];
    }

    protected function logName(): string
    {
        return "configuracion";
    }

    protected function activitylogFields(): array
    {
        return ["key", "value", "description", "is_editable"];
    }
}
