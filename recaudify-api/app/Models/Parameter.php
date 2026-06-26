<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parameter extends Model
{
    use LogsModelActivity, SoftDeletes;

    protected $fillable = ["key", "value", "description"];

    protected function logName(): string
    {
        return "catalogos";
    }

    protected function activitylogFields(): array
    {
        return ["key", "value", "description"];
    }
}
