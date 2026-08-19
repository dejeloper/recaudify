<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(private readonly RoleRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function paginate(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($search, $perPage);
    }

    public function find(int $id): ?Role
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?Role
    {
        return $this->repository->findTrashed($id);
    }

    public function trashed(): Collection
    {
        return $this->repository->trashed();
    }

    public function create(string $name, array $permissions = []): Role
    {
        // Rol y permisos son un solo hecho: un rol a medio permisar es peor que ningún rol.
        $role = DB::transaction(function () use ($name, $permissions) {
            $role = $this->repository->create(["name" => $name, "guard_name" => "api"]);

            if ($permissions) {
                $this->syncPermissionsWithLog($role, $permissions);
            }

            return $role;
        });

        return $role->load("permissions");
    }

    public function update(Role $role, ?string $name, ?array $permissions): Role
    {
        DB::transaction(function () use ($role, $name, $permissions) {
            if ($name !== null) {
                $role->update(["name" => $name]);
            }

            if ($permissions !== null) {
                $this->syncPermissionsWithLog($role, $permissions);
            }
        });

        return $role->load("permissions");
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role) {
            $role->syncPermissions([]);
            $role->delete();
        });
    }

    public function restore(Role $role): void
    {
        $role->restore();
    }

    private function syncPermissionsWithLog(Role $role, array $permissions): void
    {
        $before = $role->permissions()->pluck("name")->sort()->values()->all();

        $role->syncPermissions($permissions);

        $after = $role->permissions()->pluck("name")->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        activity($role->getActivitylogOptions()->logName)
            ->causedBy(Auth::user())
            ->performedOn($role)
            ->event("updated")
            ->withProperties([
                "attributes" => ["permissions" => $after],
                "old" => ["permissions" => $before],
            ])
            ->log("actualizó los permisos");
    }
}
