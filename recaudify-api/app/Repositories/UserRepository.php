<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
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

    public function findByUsername(string $username): ?User
    {
        return User::where("username", $username)->first();
    }

    public function search(string $term): Collection
    {
        return User::with("roles")
            ->where("name", "like", "%{$term}%")
            ->orWhere("username", "like", "%{$term}%")
            ->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
