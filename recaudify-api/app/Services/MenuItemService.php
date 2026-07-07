<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Repositories\MenuItemRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MenuItemService
{
    public function __construct(private readonly MenuItemRepository $repository) {}

    public function tree(): Collection
    {
        return $this->repository->tree();
    }

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): ?MenuItem
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?MenuItem
    {
        return $this->repository->findTrashed($id);
    }

    public function trashed(): Collection
    {
        return $this->repository->trashed();
    }

    public function create(array $data): MenuItem
    {
        $this->assertMaxDepth($data["parent_id"] ?? null);

        return $this->repository->create($data);
    }

    public function update(MenuItem $menuItem, array $data): MenuItem
    {
        if (array_key_exists("parent_id", $data) && $data["parent_id"] !== $menuItem->parent_id) {
            $this->assertMaxDepth($data["parent_id"], $menuItem);
        }

        $menuItem->update($data);

        return $menuItem->fresh("parent");
    }

    public function delete(MenuItem $menuItem): void
    {
        $menuItem->delete();
    }

    public function restore(MenuItem $menuItem): MenuItem
    {
        $menuItem->restore();

        return $menuItem;
    }

    private function assertMaxDepth(?int $parentId, ?MenuItem $self = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($self !== null && $parentId === $self->id) {
            throw ValidationException::withMessages(["parent_id" => "Un ítem no puede ser su propio padre."]);
        }

        $parent = MenuItem::find($parentId);

        if ($parent && $parent->depth() >= 2) {
            throw ValidationException::withMessages([
                "parent_id" => "No se permite más de 3 niveles de menú.",
            ]);
        }
    }
}
