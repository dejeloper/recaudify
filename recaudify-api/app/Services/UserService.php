<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class UserService
{
    public function all(): Collection
    {
        return User::with("roles")->get();
    }

    public function allDisabled(): Collection
    {
        return User::onlyTrashed()->with("roles")->get();
    }

    public function find(int $id): ?User
    {
        return User::with("roles", "permissions")->find($id);
    }

    public function findTrashed(int $id): ?User
    {
        return User::onlyTrashed()->with("roles")->find($id);
    }

    public function search(string $term): Collection
    {
        return User::with("roles")
            ->where("name", "like", "%{$term}%")
            ->orWhere("username", "like", "%{$term}%")
            ->get();
    }

    public function create(array $data, ?string $role): User
    {
        $user = User::create($data);

        if ($role) {
            $user->syncRoles([$role]);
        }

        return $user->load("roles", "permissions");
    }

    public function update(User $user, array $data, bool $syncRole = false, ?string $role = null): User
    {
        $filtered = collect($data)->filter(fn($value, $key) => $key !== "password" || !empty($value))->toArray();

        $user->update($filtered);

        if ($syncRole) {
            $user->syncRoles(array_filter([$role]));
        }

        return $user->load("roles", "permissions");
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function restore(User $user): void
    {
        $user->restore();
    }

    public function syncPermissions(User $user, array $permissions, bool $assign): SupportCollection
    {
        if ($assign) {
            $user->givePermissionTo($permissions);
        } else {
            $user->revokePermissionTo($permissions);
        }

        return $user->fresh()->getAllPermissions()->pluck("name");
    }
}
