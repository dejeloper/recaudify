<?php

namespace App\Services;

use App\Models\CallReason;
use Illuminate\Database\Eloquent\Collection;

class CallReasonService
{
    public function getAll(): Collection
    {
        return CallReason::orderBy('name')->get();
    }

    public function getTrashed(): Collection
    {
        return CallReason::onlyTrashed()->orderBy('name')->get();
    }

    public function find(int $id): ?CallReason
    {
        return CallReason::find($id);
    }

    public function findTrashed(int $id): ?CallReason
    {
        return CallReason::onlyTrashed()->find($id);
    }

    public function create(array $data): CallReason
    {
        return CallReason::create($data);
    }

    public function update(CallReason $callReason, array $data): void
    {
        $callReason->update($data);
    }

    public function delete(CallReason $callReason): void
    {
        $callReason->delete();
    }

    public function restore(CallReason $callReason): void
    {
        $callReason->restore();
    }
}
