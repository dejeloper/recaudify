<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function getAll(): Collection
    {
        return Product::orderBy('name')->get();
    }

    public function getTrashed(): Collection
    {
        return Product::onlyTrashed()->orderBy('name')->get();
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findTrashed(int $id): ?Product
    {
        return Product::onlyTrashed()->find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): void
    {
        $product->update($data);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): void
    {
        $product->restore();
    }
}
