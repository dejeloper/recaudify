<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository
{
    public function all(?string $search = null): Collection
    {
        return Branch::query()->withCount("users")->ordered()->when($search, $this->searchFilter(...))->get();
    }

    public function trashed(?string $search = null): Collection
    {
        return Branch::onlyTrashed()->withCount("users")->ordered()->when($search, $this->searchFilter(...))->get();
    }

    public function find(int $id): ?Branch
    {
        return Branch::withCount("users")->find($id);
    }

    public function findTrashed(int $id): ?Branch
    {
        return Branch::onlyTrashed()->find($id);
    }

    public function main(): ?Branch
    {
        return Branch::query()->where("is_main", true)->first();
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function demoteOtherMains(int $exceptId): void
    {
        Branch::query()
            ->where("id", "!=", $exceptId)
            ->where("is_main", true)
            ->update(["is_main" => false]);
    }

    private function searchFilter($query, string $search)
    {
        return $query->where(function ($inner) use ($search) {
            $inner
                ->where("code", "like", "%{$search}%")
                ->orWhere("name", "like", "%{$search}%")
                ->orWhere("city", "like", "%{$search}%");
        });
    }
}
