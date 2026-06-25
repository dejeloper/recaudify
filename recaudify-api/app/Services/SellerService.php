<?php

namespace App\Services;

use App\Models\Seller;
use Illuminate\Database\Eloquent\Collection;

class SellerService
{
    public function getAll(): Collection
    {
        return Seller::orderBy('name')->get();
    }

    public function getTrashed(): Collection
    {
        return Seller::onlyTrashed()->orderBy('name')->get();
    }

    public function find(int $id): ?Seller
    {
        return Seller::find($id);
    }

    public function findTrashed(int $id): ?Seller
    {
        return Seller::onlyTrashed()->find($id);
    }

    public function create(array $data): Seller
    {
        return Seller::create($data);
    }

    public function update(Seller $seller, array $data): void
    {
        $seller->update($data);
    }

    public function delete(Seller $seller): void
    {
        $seller->delete();
    }

    public function restore(Seller $seller): void
    {
        $seller->restore();
    }
}
