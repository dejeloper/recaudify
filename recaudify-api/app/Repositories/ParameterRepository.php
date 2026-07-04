<?php

namespace App\Repositories;

use App\Enums\ParameterType;
use App\Models\Parameter;
use Illuminate\Database\Eloquent\Collection;

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

    public function find(int $id): ?Parameter
    {
        return Parameter::find($id);
    }

    public function findOrFail(int $id): Parameter
    {
        return Parameter::findOrFail($id);
    }

    public function create(array $data): Parameter
    {
        return Parameter::create($data);
    }
}
