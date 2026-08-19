<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Todo monto del sistema es un entero de pesos colombianos.
 *
 * No hay centavos: el peso no se fracciona en la operación real de cobranza, y guardar decimales
 * abre la puerta a errores de redondeo que después no se pueden reconstruir. Las columnas de dinero
 * son `bigInteger` y los modelos las castean a `integer` — nunca `decimal` ni `float`.
 */
final class Money
{
    /** Unidad de redondeo del negocio: el pago mínimo por mora se redondea al millar. */
    public const THOUSAND = 1000;

    /**
     * Redondea al millar superior, que es la regla del legacy (`calcularSaldoMinimo`).
     *
     * Siempre hacia arriba: cobrar de menos deja saldos de $300 imposibles de cerrar.
     */
    public static function roundUpToThousand(int|float $amount): int
    {
        return (int) (ceil($amount / self::THOUSAND) * self::THOUSAND);
    }

    /** Redondea al millar más cercano. Para montos informativos, no para lo que se cobra. */
    public static function roundToThousand(int|float $amount): int
    {
        return (int) (round($amount / self::THOUSAND) * self::THOUSAND);
    }

    /**
     * Convierte a entero de pesos un valor que llega de fuera (request, CSV, cálculo intermedio).
     *
     * Rechaza lo que no es representable en vez de truncar en silencio.
     */
    public static function fromInput(int|float|string $value): int
    {
        if (is_string($value)) {
            $clean = str_replace([".", ",", " ", "$"], "", trim($value));

            if ($clean === "" || !is_numeric($clean)) {
                throw new InvalidArgumentException("Monto inválido: {$value}");
            }

            $value = (float) $clean;
        }

        if (!is_finite((float) $value)) {
            throw new InvalidArgumentException("Monto no finito.");
        }

        return (int) round($value);
    }

    /** Reparte un monto entre N cuotas sin perder ni inventar pesos por redondeo. */
    public static function split(int $amount, int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException("El número de partes debe ser al menos 1.");
        }

        $base = intdiv($amount, $parts);
        $remainder = $amount - $base * $parts;

        // El sobrante se carga a las primeras cuotas: la suma del plan siempre cuadra con el total.
        return array_map(fn(int $i) => $i < $remainder ? $base + 1 : $base, range(0, $parts - 1));
    }
}
