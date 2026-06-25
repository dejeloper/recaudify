<?php

namespace App\Services;

use App\Models\State;
use Illuminate\Database\Eloquent\Collection;

class StateService
{
    public function getAll(?string $type = null): Collection
    {
        return State::when($type, fn ($query) => $query->where('state_type', $type))
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): ?State
    {
        return State::find($id);
    }
}
