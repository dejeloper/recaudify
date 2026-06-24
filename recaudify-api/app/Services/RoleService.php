<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    public function all(): Collection
    {
        return Role::where('guard_name', 'api')->with('permissions')->orderBy('name')->get();
    }

    public function find(int $id): ?Role
    {
        return Role::where('guard_name', 'api')->with('permissions')->find($id);
    }

    public function findTrashed(int $id): ?Role
    {
        return Role::onlyTrashed()->where('guard_name', 'api')->find($id);
    }

    public function trashed(): Collection
    {
        return Role::onlyTrashed()->where('guard_name', 'api')->with('permissions')->orderBy('name')->get();
    }

    public function create(string $name, array $permissions = []): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'api']);

        if ($permissions) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function update(Role $role, ?string $name, ?array $permissions): Role
    {
        if ($name !== null) {
            $role->update(['name' => $name]);
        }

        if ($permissions !== null) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function delete(Role $role): void
    {
        $role->syncPermissions([]);
        $role->delete();
    }

    public function restore(Role $role): void
    {
        $role->restore();
    }
}
