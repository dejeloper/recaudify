<?php

namespace App\Support;

use App\Enums\ParameterType;
use App\Services\ParameterService;
use Throwable;

/**
 * Lee de parámetros la hora de una tarea programada.
 *
 * `routes/console.php` se evalúa en cada comando de artisan, incluido `migrate` sobre una base
 * vacía. Por eso todo acá es defensivo: si la tabla no existe, la caché falla o alguien guardó un
 * valor inválido, se cae al valor por defecto en vez de romper la consola entera.
 */
final class ParameterSchedule
{
    private const TIME_FORMAT = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public static function time(string $key, string $default = "03:00"): string
    {
        try {
            $value = app(ParameterService::class)->get(ParameterType::Application, $key);
        } catch (Throwable) {
            return $default;
        }

        return self::isValidTime($value) ? (string) $value : $default;
    }

    private static function isValidTime(mixed $value): bool
    {
        return is_string($value) && preg_match(self::TIME_FORMAT, $value) === 1;
    }
}
