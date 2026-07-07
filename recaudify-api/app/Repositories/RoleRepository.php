<?php

namespace App\Repositories;

use App\Models\Role;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository
{
    private function baseQuery()
    {
        return Role::where("guard_name", "api")->where("name", "!=", AppServiceProvider::SUPERADMIN_ROLE);
    }

    public function all(): Collection
    {
        return $this->baseQuery()->with("permissions")->orderBy("name")->get();
    }

    public function find(int $id): ?Role
    {
        return $this->baseQuery()->with("permissions")->find($id);
    }

    public function findTrashed(int $id): ?Role
    {
        return Role::onlyTrashed()
            ->where("guard_name", "api")
            ->where("name", "!=", AppServiceProvider::SUPERADMIN_ROLE)
            ->find($id);
    }

    public function trashed(): Collection
    {
        return Role::onlyTrashed()
            ->where("guard_name", "api")
            ->where("name", "!=", AppServiceProvider::SUPERADMIN_ROLE)
            ->with("permissions")
            ->orderBy("name")
            ->get();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }
}
