<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ParameterService
{
    private const CACHE_KEY = "parameters.all";

    private const TTL = 86400;

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()->get($key, $default);
    }

    public static function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            return Parameter::pluck("value", "key");
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
