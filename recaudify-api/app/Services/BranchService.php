<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\BranchRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function __construct(private readonly BranchRepository $repository) {}

    public function all(?string $search = null): Collection
    {
        return $this->repository->all($search);
    }

    public function trashed(?string $search = null): Collection
    {
        return $this->repository->trashed($search);
    }

    public function find(int $id): ?Branch
    {
        return $this->repository->find($id);
    }

    public function findTrashed(int $id): ?Branch
    {
        return $this->repository->findTrashed($id);
    }

    public function main(): ?Branch
    {
        return $this->repository->main();
    }

    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $data["is_main"] = (bool) ($data["is_main"] ?? false) || !$this->repository->main();

            $branch = $this->repository->create($data);

            if ($branch->is_main) {
                $this->repository->demoteOtherMains($branch->id);
            }

            return $branch;
        });
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $this->assertKeepsAMainBranch($branch, $data);

            $branch->update($data);

            if ($branch->is_main) {
                $this->repository->demoteOtherMains($branch->id);
            }

            return $branch->fresh();
        });
    }

    public function delete(Branch $branch): void
    {
        $this->assertNotMain($branch);
        $this->assertHasNoUsers($branch);

        $branch->delete();
    }

    public function restore(Branch $branch): Branch
    {
        $branch->restore();

        return $branch;
    }

    private function assertKeepsAMainBranch(Branch $branch, array $data): void
    {
        if (!$branch->is_main || ($data["is_main"] ?? true)) {
            return;
        }

        throw ValidationException::withMessages([
            "is_main" =>
                "Debe haber una sucursal principal. Marque otra como principal en vez de quitarle el flag a esta.",
        ]);
    }

    private function assertNotMain(Branch $branch): void
    {
        if (!$branch->is_main) {
            return;
        }

        throw ValidationException::withMessages([
            "branch" => "No se puede eliminar la sucursal principal. Marque otra como principal primero.",
        ]);
    }

    private function assertHasNoUsers(Branch $branch): void
    {
        $count = $branch->users()->count();

        if ($count === 0) {
            return;
        }

        throw ValidationException::withMessages([
            "branch" => "No se puede eliminar: {$count} usuario(s) pertenecen a esta sucursal. Reasígnelos primero.",
        ]);
    }
}
