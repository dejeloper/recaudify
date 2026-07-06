<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class UserService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function allDisabled(): Collection
    {
        return $this->repository->allDisabled();
    }

    public function find(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?User
    {
        return $this->repository->findTrashed($id);
    }

    public function findByUsername(string $username): ?User
    {
        return $this->repository->findByUsername($username);
    }

    public function findByLoginField(string $field, string $value): ?User
    {
        return $this->repository->findByLoginField($field, $value);
    }

    public function search(string $term): Collection
    {
        return $this->repository->search($term);
    }

    public function create(array $data, ?string $role = null): User
    {
        $user = $this->repository->create($data);

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
