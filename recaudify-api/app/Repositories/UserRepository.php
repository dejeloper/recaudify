<?php

namespace App\Repositories;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    private function excludeSuperadmin($query)
    {
        return $query->whereDoesntHave("roles", fn($q) => $q->where("name", AppServiceProvider::SUPERADMIN_ROLE));
    }

    public function all(): Collection
    {
        return $this->excludeSuperadmin(User::with("roles.permissions", "permissions"))->get();
    }

    public function allDisabled(): Collection
    {
        return $this->excludeSuperadmin(User::onlyTrashed()->with("roles.permissions", "permissions"))->get();
    }

    public function find(int $id): ?User
    {
        return $this->excludeSuperadmin(User::with("roles.permissions", "permissions"))->find($id);
    }

    public function findTrashed(int $id): ?User
    {
        return $this->excludeSuperadmin(User::onlyTrashed()->with("roles.permissions", "permissions"))->find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::where("username", $username)->first();
    }

    public function findByLoginField(string $field, string $value): ?User
    {
        return User::where($field, $value)->first();
    }

    public function search(string $term): Collection
    {
        return $this->excludeSuperadmin(User::with("roles.permissions", "permissions"))
            ->where(fn($q) => $q->where("name", "like", "%{$term}%")->orWhere("username", "like", "%{$term}%"))
            ->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
