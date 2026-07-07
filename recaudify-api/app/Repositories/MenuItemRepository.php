<?php

namespace App\Repositories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;

class MenuItemRepository
{
    public function tree(): Collection
    {
        return MenuItem::whereNull("parent_id")
            ->where("is_active", true)
            ->with([
                "children" => fn($query) => $query->where("is_active", true),
                "children.children" => fn($query) => $query->where("is_active", true),
            ])
            ->orderBy("order")
            ->get();
    }

    public function all(): Collection
    {
        return MenuItem::with("parent")->orderBy("parent_id")->orderBy("order")->get();
    }

    public function find(int $id): ?MenuItem
    {
        return MenuItem::with("parent")->find($id);
    }

    public function findTrashed(int $id): ?MenuItem
    {
        return MenuItem::onlyTrashed()->find($id);
    }

    public function trashed(): Collection
    {
        return MenuItem::onlyTrashed()->with("parent")->orderBy("parent_id")->orderBy("order")->get();
    }

    public function create(array $data): MenuItem
    {
        return MenuItem::create($data);
    }
}
