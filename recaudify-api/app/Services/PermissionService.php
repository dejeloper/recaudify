<?php

namespace App\Services;

use App\Models\Permission;
use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function __construct(private readonly PermissionRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): ?Permission
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?Permission
    {
        return $this->repository->findTrashed($id);
    }

    public function trashed(): Collection
    {
        return $this->repository->trashed();
    }

    public function create(string $name): Permission
    {
        return $this->repository->create(["name" => $name, "guard_name" => "api"]);
    }

    public function update(Permission $permission, string $name): void
    {
        $permission->update(["name" => $name]);
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
