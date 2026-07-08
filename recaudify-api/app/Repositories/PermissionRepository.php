<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PermissionRepository
{
    public function all(): Collection
    {
        return Permission::where("guard_name", "api")->orderBy("name")->get();
    }

    public function paginate(?string $search, int $perPage): LengthAwarePaginator
    {
        return Permission::where("guard_name", "api")
            ->when($search, fn($q, $s) => $q->where("name", "like", "%{$s}%"))
            ->orderBy("name")
            ->paginate($perPage);
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
