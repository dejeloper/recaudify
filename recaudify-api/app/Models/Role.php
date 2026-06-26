<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsModelActivity, SoftDeletes;

    protected string $guard_name = "api";

    protected function logName(): string
    {
        return "seguridad";
    }

    protected function activitylogFields(): array
    {
        return ["name"];
    }
}
