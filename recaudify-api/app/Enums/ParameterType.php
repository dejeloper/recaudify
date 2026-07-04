<?php

namespace App\Enums;

enum ParameterType: string
{
    case Authentication = "authentication";
    case Application = "application";
    case Business = "business";
    case Configuration = "configuration";
    case Notification = "notification";
    case Security = "security";

    public function label(): string
    {
        return match ($this) {
            self::Authentication => "Autenticación",
            self::Application => "Aplicación",
            self::Business => "Negocio",
            self::Configuration => "Configuración",
            self::Notification => "Notificaciones",
            self::Security => "Seguridad",
        };
    }
}
