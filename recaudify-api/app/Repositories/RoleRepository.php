<?php

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository
{
    public function all(): Collection
    {
        return Role::where("guard_name", "api")->with("permissions")->orderBy("name")->get();
    }

    public function find(int $id): ?Role
    {
        return Role::where("guard_name", "api")->with("permissions")->find($id);
    }

    public function findTrashed(int $id): ?Role
    {
        return Role::onlyTrashed()->where("guard_name", "api")->find($id);
    }

    public function trashed(): Collection
    {
        return Role::onlyTrashed()->where("guard_name", "api")->with("permissions")->orderBy("name")->get();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }
}
