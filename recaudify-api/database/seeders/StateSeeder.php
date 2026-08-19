<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateTransition;
use Illuminate\Database\Seeder;

/**
 * Ciclos de vida iniciales.
 *
 * Estos son los estados con los que arranca el negocio; agregar uno nuevo es un INSERT acá o desde
 * la pantalla de administración, sin tocar código.
 */
class StateSeeder extends Seeder
{
    /**
     * [entidad => [clave, nombre, inicial, final, color]]
     */
    private const STATES = [
        "client" => [
            ["prospect", "Prospecto", true, false, "#94a3b8"],
            ["active", "Activo", false, false, "#22c55e"],
            ["suspended", "Suspendido", false, false, "#f59e0b"],
            ["inactive", "Inactivo", false, true, "#64748b"],
        ],
        "contract" => [
            ["draft", "Borrador", true, false, "#94a3b8"],
            ["pending_validation", "Pendiente de validación", false, false, "#eab308"],
            ["active", "Activo", false, false, "#22c55e"],
            ["suspended", "Suspendido", false, false, "#f59e0b"],
            ["cancelled", "Cancelado", false, true, "#ef4444"],
            ["finished", "Finalizado", false, true, "#3b82f6"],
        ],
        "payment" => [
            ["pending", "Pendiente", true, false, "#eab308"],
            ["confirmed", "Confirmado", false, false, "#22c55e"],
            ["reversed", "Reversado", false, true, "#ef4444"],
        ],
        "commitment" => [
            ["open", "Vigente", true, false, "#3b82f6"],
            ["fulfilled", "Cumplido", false, true, "#22c55e"],
            ["broken", "Incumplido", false, true, "#ef4444"],
            ["cancelled", "Anulado", false, true, "#64748b"],
        ],
    ];

    /**
     * [entidad => [desde|null, hacia, permiso, automática, exige autorización, exige motivo]]
     */
    private const TRANSITIONS = [
        "client" => [
            [null, "prospect", null, false, false, false],
            ["prospect", "active", "clients.edit", false, false, false],
            ["active", "suspended", "clients.edit", false, true, true],
            ["suspended", "active", "clients.edit", false, true, true],
            ["active", "inactive", "clients.edit", false, false, true],
            ["prospect", "inactive", "clients.edit", false, false, true],
        ],
        "contract" => [
            [null, "draft", null, false, false, false],
            ["draft", "pending_validation", "contracts.edit", false, false, false],
            // El paso a activo es el que habilita el cobro: exige que alguien valide los datos.
            ["pending_validation", "active", "contracts.validate", false, false, false],
            ["pending_validation", "cancelled", "contracts.cancel", false, false, true],
            // Suspender un contrato vivo congela la mora: siempre con autorización y motivo.
            ["active", "suspended", "contracts.suspend", false, true, true],
            ["suspended", "active", "contracts.suspend", false, true, true],
            ["active", "cancelled", "contracts.cancel", false, true, true],
            ["suspended", "cancelled", "contracts.cancel", false, true, true],
            // Lo ejecuta el motor financiero cuando el saldo llega a cero.
            ["active", "finished", null, true, false, false],
            ["draft", "cancelled", "contracts.cancel", false, false, false],
        ],
        "payment" => [
            [null, "pending", null, false, false, false],
            ["pending", "confirmed", "payments.confirm", false, false, false],
            // El reverso nunca borra el pago: lo mueve a un estado final y queda visible.
            ["confirmed", "reversed", "payments.reverse", false, true, true],
            ["pending", "reversed", "payments.reverse", false, false, true],
        ],
        "commitment" => [
            [null, "open", null, false, false, false],
            ["open", "fulfilled", null, true, false, false],
            // Vence sin confirmarse: lo marca el job, no una persona.
            ["open", "broken", null, true, false, false],
            ["open", "cancelled", "managements.edit", false, false, true],
        ],
    ];

    public function run(): void
    {
        foreach (self::STATES as $entity => $states) {
            foreach ($states as $order => [$key, $name, $isInitial, $isFinal, $color]) {
                State::updateOrCreate(
                    ["entity" => $entity, "key" => $key],
                    [
                        "name" => $name,
                        "is_initial" => $isInitial,
                        "is_final" => $isFinal,
                        "color" => $color,
                        "sort_order" => $order,
                    ],
                );
            }
        }

        foreach (self::TRANSITIONS as $entity => $transitions) {
            foreach ($transitions as [$from, $to, $permission, $automatic, $authorization, $reason]) {
                $fromId = $from ? State::where("entity", $entity)->where("key", $from)->value("id") : null;
                $toId = State::where("entity", $entity)->where("key", $to)->value("id");

                StateTransition::updateOrCreate(
                    ["entity" => $entity, "from_state_id" => $fromId, "to_state_id" => $toId],
                    [
                        "permission" => $permission,
                        "is_automatic" => $automatic,
                        "requires_authorization" => $authorization,
                        "requires_reason" => $reason,
                    ],
                );
            }
        }
    }
}
