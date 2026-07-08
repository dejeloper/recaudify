<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            //  Authentication
            [
                "type" => "authentication",
                "key" => "shift_status_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Habilita la verificación de horario al iniciar sesión",
            ],
            [
                "type" => "authentication",
                "key" => "shift_countdown_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Muestra el contador regresivo del turno activo",
            ],
            [
                "type" => "authentication",
                "key" => "geolocalization_login_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Solicita geolocalización al momento del login",
            ],
            [
                "type" => "authentication",
                "key" => "login_field",
                "value" => "username",
                "cast" => "string",
                "description" => "Campo usado para autenticar al usuario: username o email",
            ],
            [
                "type" => "authentication",
                "key" => "reset_password_mode",
                "value" => "fixed",
                "cast" => "string",
                "description" =>
                    "Modo de contraseña al resetear un usuario: fixed (valor fijo) o random (auto-generada)",
            ],
            [
                "type" => "authentication",
                "key" => "reset_password_fixed_value",
                "value" => "Cobranza123",
                "cast" => "string",
                "description" =>
                    "Valor fijo usado al resetear contraseña cuando reset_password_mode=fixed. Si está vacío, se genera una contraseña aleatoria como salvaguarda",
            ],

            //  Application ─
            [
                "type" => "application",
                "key" => "pagination_per_page",
                "value" => "25",
                "cast" => "integer",
                "description" => "Registros por página en listados",
                "is_editable" => false,
            ],
            [
                "type" => "application",
                "key" => "pagination_max_per_page",
                "value" => "100",
                "cast" => "integer",
                "description" => "Máximo de registros por página permitido",
                "is_editable" => false,
            ],
            [
                "type" => "application",
                "key" => "timezone",
                "value" => "America/Bogota",
                "cast" => "string",
                "description" => "Zona horaria de la aplicación",
            ],
            [
                "type" => "application",
                "key" => "currency",
                "value" => "COP",
                "cast" => "string",
                "description" => "Moneda principal del sistema",
            ],
            [
                "type" => "application",
                "key" => "date_format",
                "value" => "DD/MM/YYYY",
                "cast" => "string",
                "description" => "Formato de fecha para la interfaz",
            ],

            //  Security
            [
                "type" => "security",
                "key" => "lockout_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" =>
                    "Habilita el bloqueo temporal de usuario e IP tras superar los intentos fallidos de login",
            ],
            [
                "type" => "security",
                "key" => "max_login_attempts",
                "value" => "5",
                "cast" => "integer",
                "description" => "Intentos de login fallidos antes de bloquear",
            ],
            [
                "type" => "security",
                "key" => "lockout_minutes",
                "value" => "15",
                "cast" => "integer",
                "description" => "Minutos de bloqueo tras superar los intentos fallidos",
            ],
            [
                "type" => "security",
                "key" => "session_timeout_minutes",
                "value" => "2",
                "cast" => "integer",
                "description" => "Minutos de inactividad antes de cerrar la sesión automáticamente",
            ],
            [
                "type" => "security",
                "key" => "password_min_length",
                "value" => "8",
                "cast" => "integer",
                "description" => "Longitud mínima exigida para las contraseñas",
            ],
            [
                "type" => "security",
                "key" => "password_require_uppercase",
                "value" => "false",
                "cast" => "boolean",
                "description" => "Exige al menos una letra mayúscula en la contraseña",
            ],
            [
                "type" => "security",
                "key" => "password_require_numbers",
                "value" => "false",
                "cast" => "boolean",
                "description" => "Exige al menos un número en la contraseña",
            ],
            [
                "type" => "security",
                "key" => "password_require_symbols",
                "value" => "false",
                "cast" => "boolean",
                "description" => "Exige al menos un símbolo en la contraseña",
            ],
            [
                "type" => "security",
                "key" => "password_expiration_days",
                "value" => "0",
                "cast" => "integer",
                "description" => "Días antes de forzar el cambio de contraseña (0 = nunca expira)",
            ],

            //  Configuration ─
            [
                "type" => "configuration",
                "key" => "maintenance_mode",
                "value" => "false",
                "cast" => "boolean",
                "description" => "Activa el modo mantenimiento (bloquea acceso a usuarios)",
            ],
            [
                "type" => "configuration",
                "key" => "parameter_types",
                "value" => '["authentication","application","business","configuration","notification","security"]',
                "cast" => "json",
                "description" => "Tipos de parámetro disponibles en el sistema",
                "is_editable" => false,
            ],
            [
                "type" => "configuration",
                "key" => "parameter_casts",
                "value" => '["string","boolean","integer","float","json"]',
                "cast" => "json",
                "description" => "Tipos de cast disponibles para los valores de parámetros",
                "is_editable" => false,
            ],

            //  Notification
            [
                "type" => "notification",
                "key" => "email_notifications_enabled",
                "value" => "false",
                "cast" => "boolean",
                "description" => "Habilita el envío de notificaciones por correo",
            ],
            [
                "type" => "notification",
                "key" => "toast_duration_ms",
                "value" => "5000",
                "cast" => "integer",
                "description" => "Duración en milisegundos de los mensajes toast en la interfaz",
            ],
        ];

        foreach ($parameters as $param) {
            Parameter::firstOrCreate(
                ["type" => $param["type"], "key" => $param["key"]],
                array_merge(["is_editable" => true], $param),
            );
        }
    }
}
