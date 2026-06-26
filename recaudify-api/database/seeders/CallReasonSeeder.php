<?php

namespace Database\Seeders;

use App\Models\CallReason;
use Illuminate\Database\Seeder;

class CallReasonSeeder extends Seeder
{
    /**
     * Motivos de llamada del catálogo legacy (códigos preservados).
     * 100 = "Pendiente" queda inactivo (era el comodín por defecto).
     */
    public function run(): void
    {
        $reasons = [
            [100, "Pendiente", "", false],
            [101, "Programar Pago", "green", true],
            [102, "Cliente no Paga", "red", true],
            [103, "Llamar más tarde", "orange", true],
            [104, "Llamar otro día", "black", true],
        ];

        foreach ($reasons as [$id, $name, $color, $active]) {
            CallReason::updateOrCreate(["id" => $id], ["name" => $name, "color" => $color, "active" => $active]);
        }
    }
}
