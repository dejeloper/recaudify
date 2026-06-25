<?php

namespace App\Services;

use App\Models\Rate;
use Illuminate\Database\Eloquent\Collection;

class RateService
{
    public function getAll(): Collection
    {
        return Rate::with('product')->orderBy('name')->get();
    }

    public function getTrashed(): Collection
    {
        return Rate::onlyTrashed()->with('product')->orderBy('name')->get();
    }

    public function find(int $id): ?Rate
    {
        return Rate::with('product')->find($id);
    }

    public function findTrashed(int $id): ?Rate
    {
        return Rate::onlyTrashed()->find($id);
    }

    public function create(array $data): Rate
    {
        return Rate::create($data)->load('product');
    }

    public function update(Rate $rate, array $data): void
    {
        $rate->update($data);
    }

    public function delete(Rate $rate): void
    {
        $rate->delete();
    }

    public function restore(Rate $rate): void
    {
        $rate->restore();
    }
}
