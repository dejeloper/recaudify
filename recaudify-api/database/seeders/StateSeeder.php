<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Estados del sistema, preservando los códigos legacy (101–127) como id.
     * El agrupador (legacy TipoEstado) se guarda como string en state_type.
     */
    public function run(): void
    {
        $states = [
            // Usuarios (legacy TipoEstado 101)
            [101, "Activo", "user"],
            [102, "Inactivo", "user"],
            [103, "Bloqueado", "user"],
            // Clientes (legacy TipoEstado 102)
            [104, "Al día", "client"],
            [105, "Debe", "client"],
            [106, "Devolución", "client"],
            // Vendedores (legacy TipoEstado 103)
            [107, "Activo", "seller"],
            [108, "Vacaciones", "seller"],
            [109, "Inactivo", "seller"],
            // Pedidos / Contratos (legacy TipoEstado 104)
            [110, "Sin Pago", "contract"],
            [111, "Al día", "contract"],
            [112, "Deuda", "contract"],
            [113, "Devolución", "contract"],
            [114, "Paz y Salvo", "contract"],
            // Clientes (continuación)
            [115, "DataCredito", "client"],
            // Pagos Programados (legacy TipoEstado 105)
            [116, "Programado", "scheduled_payment"],
            [117, "Pagado", "scheduled_payment"],
            [118, "No Pagado", "scheduled_payment"],
            // Cobradores (legacy TipoEstado 106)
            [119, "En Operación", "collector"],
            [120, "Inactivo", "collector"],
            [121, "Bloqueado", "collector"],
            // Pagos Programados (continuación)
            [122, "Descartado", "scheduled_payment"],
            // Clientes (continuación)
            [123, "Paz y Salvo", "client"],
            [124, "En Mora", "client"],
            // Pedidos / Contratos (continuación)
            [125, "DataCredito", "contract"],
            // Clientes (continuación)
            [126, "Reportado", "client"],
            // Pedidos / Contratos (continuación)
            [127, "Reportado", "contract"],
        ];

        foreach ($states as [$id, $name, $type]) {
            State::updateOrCreate(["id" => $id], ["name" => $name, "state_type" => $type, "active" => true]);
        }
    }
}
