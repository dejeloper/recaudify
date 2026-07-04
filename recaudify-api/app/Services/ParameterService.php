<?php

namespace App\Services;

use App\Enums\ParameterCast;
use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Repositories\ParameterRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ParameterService
{
    private const CACHE_TTL = 300;

    public function __construct(private readonly ParameterRepository $repository) {}

    public function all(?ParameterType $type = null): Collection
    {
        return $this->repository->all($type);
    }

    public function find(int $id): ?Parameter
    {
        return $this->repository->find($id);
    }

    public function findOrFail(int $id): Parameter
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Parameter
    {
        $parameter = $this->repository->create($data);
        $this->flushCache($parameter->type);

        return $parameter;
    }

    public function getAll(ParameterType $type): Collection
    {
        return Cache::remember(
            "parameters.{$type->value}",
            self::CACHE_TTL,
            fn() => $this->repository->allByType($type),
        );
    }

    public function get(ParameterType $type, string $key): mixed
    {
        $param = $this->getAll($type)->firstWhere("key", $key);

        return $param ? $this->resolveValue($param->value, $param->cast) : null;
    }

    public function update(Parameter $parameter, string $value): Parameter
    {
        $parameter->update(["value" => $value]);
        $this->flushCache($parameter->type);

        return $parameter->fresh();
    }

    public function delete(Parameter $parameter): void
    {
        $parameter->delete();
        $this->flushCache($parameter->type);
    }

    public function flushCache(ParameterType $type): void
    {
        Cache::forget("parameters.{$type->value}");
    }

    public function resolveValue(string $value, ParameterCast $cast): mixed
    {
        return match ($cast) {
            ParameterCast::Boolean => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ParameterCast::Integer => (int) $value,
            ParameterCast::Float => (float) $value,
            ParameterCast::Json => json_decode($value, true),
            ParameterCast::String => $value,
        };
    }
}
