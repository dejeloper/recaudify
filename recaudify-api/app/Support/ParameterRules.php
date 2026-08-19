<?php

namespace App\Support;

use App\Enums\ParameterCast;

/**
 * Reglas de validación del valor de un parámetro.
 *
 * Sin esto, `value` solo se valida como "string" y cualquier disparate se guarda sin protestar: el
 * error aparece después, lejos, cuando algo intenta usarlo. Y con parámetros que gobiernan mora,
 * porcentajes y bloqueos de acceso, "después" es tarde.
 *
 * Dos capas: lo que exige el `cast` (aplica a todos) y lo que exige la clave concreta.
 */
final class ParameterRules
{
    /** Reglas propias de cada parámetro, más allá de su tipo. */
    private const KEY_RULES = [
        // Autenticación
        "login_field" => ["in:username,email"],
        "reset_password_mode" => ["in:fixed,random"],
        "password_min_length" => ["integer", "min:4", "max:64"],
        "password_expiration_days" => ["integer", "min:0", "max:3650"],
        "max_login_attempts" => ["integer", "min:1", "max:50"],
        "lockout_minutes" => ["integer", "min:1", "max:1440"],

        // Sesión
        "session_timeout_minutes" => ["integer", "min:1", "max:1440"],

        // Presentación
        "timezone" => ["timezone"],
        "currency" => ["string", "size:3", "alpha"],
        "pagination_per_page" => ["integer", "min:1", "max:500"],
        "pagination_max_per_page" => ["integer", "min:1", "max:500"],
        "toast_duration_ms" => ["integer", "min:500", "max:60000"],

        // Auditoría
        "activity_log_retention_days" => ["integer", "min:1", "max:3650"],
        "activity_log_purge_time" => ['regex:/^([01]\d|2[0-3]):[0-5]\d$/'],

        // Mantenimiento
        "maintenance_scope" => ["in:all,writes"],
        "maintenance_message" => ["string", "max:255"],
    ];

    private const MESSAGES = [
        "login_field" => "Debe ser 'username' o 'email'.",
        "reset_password_mode" => "Debe ser 'fixed' o 'random'.",
        "maintenance_scope" => "Debe ser 'all' (bloquea todo) o 'writes' (bloquea solo guardar).",
        "activity_log_purge_time" => "Debe tener formato HH:MM en 24 horas (ej. 03:00).",
        "timezone" => "Debe ser una zona horaria válida (ej. America/Bogota).",
        "currency" => "Debe ser un código de 3 letras (ej. COP).",
    ];

    public static function for(string $key, ?ParameterCast $cast): array
    {
        return array_merge(self::castRules($cast), self::KEY_RULES[$key] ?? []);
    }

    /** Mensaje en español para la regla de una clave, si tiene uno propio. */
    public static function messageFor(string $key): ?string
    {
        return self::MESSAGES[$key] ?? null;
    }

    public static function hasRulesFor(string $key): bool
    {
        return isset(self::KEY_RULES[$key]);
    }

    private static function castRules(?ParameterCast $cast): array
    {
        return match ($cast) {
            ParameterCast::Boolean => ["in:true,false,1,0"],
            ParameterCast::Integer => ['regex:/^-?\d+$/'],
            ParameterCast::Float => ["numeric"],
            ParameterCast::Json => ["json"],
            default => [],
        };
    }
}
