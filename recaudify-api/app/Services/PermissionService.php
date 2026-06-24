<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function all(): Collection
    {
        return Permission::where('guard_name', 'api')->orderBy('name')->get();
    }

    public function find(int $id): ?Permission
    {
        return Permission::where('guard_name', 'api')->find($id);
    }

    public function findTrashed(int $id): ?Permission
    {
        return Permission::onlyTrashed()->where('guard_name', 'api')->find($id);
    }

    public function trashed(): Collection
    {
        return Permission::onlyTrashed()->where('guard_name', 'api')->orderBy('name')->get();
    }

    public function create(string $name): Permission
    {
        return Permission::create(['name' => $name, 'guard_name' => 'api']);
    }

    public function update(Permission $permission, string $name): void
    {
        $permission->update(['name' => $name]);
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }

    public function restore(Permission $permission): void
    {
        $permission->restore();
    }
}
