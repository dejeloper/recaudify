<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

class ParameterService
{
    private const CACHE_KEY = 'parameters.all';

    private const TTL = 86400;

    // --- Cache helpers (static, usable without injection) ---

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()->get($key, $default);
    }

    public static function all(): SupportCollection
    {
        $data = Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return Parameter::pluck('value', 'key')->all();
        });

        return collect($data);
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // --- CRUD (instance methods, inject via constructor) ---

    public function getAll(): Collection
    {
        return Parameter::orderBy('key')->get();
    }

    public function getTrashed(): Collection
    {
        return Parameter::onlyTrashed()->orderBy('key')->get();
    }

    public function find(int $id): ?Parameter
    {
        return Parameter::find($id);
    }

    public function findTrashed(int $id): ?Parameter
    {
        return Parameter::onlyTrashed()->find($id);
    }

    public function create(array $data): Parameter
    {
        $parameter = Parameter::create($data);
        self::clearCache();

        return $parameter;
    }

    public function update(Parameter $parameter, array $data): void
    {
        $parameter->update($data);
        self::clearCache();
    }

    public function delete(Parameter $parameter): void
    {
        $parameter->delete();
        self::clearCache();
    }

    public function restore(Parameter $parameter): void
    {
        $parameter->restore();
        self::clearCache();
    }
}
