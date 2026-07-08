<?php

namespace App\Repositories;

use App\Enums\ParameterType;
use App\Models\Parameter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ParameterRepository
{
    public function allByType(ParameterType $type): Collection
    {
        return Parameter::where("type", $type->value)->get();
    }

    public function all(?ParameterType $type = null): Collection
    {
        $query = Parameter::query()->orderBy("type")->orderBy("key");

        if ($type !== null) {
            $query->where("type", $type->value);
        }

        return $query->get();
    }

    public function paginate(?ParameterType $type, ?string $search, int $perPage): LengthAwarePaginator
    {
        return Parameter::query()
            ->when($type !== null, fn($q) => $q->where("type", $type->value))
            ->when(
                $search,
                fn($q, $s) => $q->where(
                    fn($q2) => $q2->where("key", "like", "%{$s}%")->orWhere("description", "like", "%{$s}%"),
                ),
            )
            ->orderBy("type")
            ->orderBy("key")
            ->paginate($perPage);
    }

    public function find(int $id): ?Parameter
    {
        return Parameter::find($id);
    }

    public function findOrFail(int $id): Parameter
    {
        return Parameter::findOrFail($id);
    }

    public function findTrashed(int $id): ?Parameter
    {
        return Parameter::onlyTrashed()->find($id);
    }

    public function trashed(): Collection
    {
        return Parameter::onlyTrashed()->orderBy("type")->orderBy("key")->get();
    }

    public function create(array $data): Parameter
    {
        return Parameter::create($data);
    }
}
