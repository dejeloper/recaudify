<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository
{
    public function all(): Collection
    {
        return Permission::where("guard_name", "api")->orderBy("name")->get();
    }

    public function find(int $id): ?Permission
    {
        return Permission::where("guard_name", "api")->find($id);
    }

    public function findTrashed(int $id): ?Permission
    {
        return Permission::onlyTrashed()->where("guard_name", "api")->find($id);
    }

    public function trashed(): Collection
    {
        return Permission::onlyTrashed()->where("guard_name", "api")->orderBy("name")->get();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }
}
