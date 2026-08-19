<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Un cambio de estado que el catálogo de transiciones no permite, o que no cumple sus condiciones
 * (permiso, motivo, autorización previa).
 */
class StateTransitionException extends RuntimeException
{
    public static function notAllowed(string $entity, ?string $from, string $to): self
    {
        $desde = $from ?? "(creación)";

        return new self("La entidad {$entity} no puede pasar de {$desde} a {$to}.");
    }

    public static function unknownState(string $entity, string $key): self
    {
        return new self("El estado {$key} no existe para la entidad {$entity}.");
    }

    public static function missingInitialState(string $entity): self
    {
        return new self("La entidad {$entity} no tiene estado inicial configurado.");
    }

    public static function reasonRequired(string $to): self
    {
        return new self("El cambio a {$to} exige indicar un motivo.");
    }

    public static function authorizationRequired(string $to): self
    {
        return new self("El cambio a {$to} exige autorización previa de un administrador o coordinador.");
    }

    public static function forbidden(string $to, string $permission): self
    {
        return new self("No tiene el permiso {$permission} para pasar a {$to}.");
    }

    public static function automaticOnly(string $to): self
    {
        return new self("El cambio a {$to} lo ejecuta el sistema, no puede hacerse a mano.");
    }
}
