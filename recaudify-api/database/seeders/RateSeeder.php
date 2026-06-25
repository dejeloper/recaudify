<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    /**
     * Tarifas del catálogo legacy. Preserva los códigos:
     * id 1 = tarifa importada sin valor; 101–119 = tarifas por producto.
     * Formato: [id, nombre, product_id, valor, cuotas, valor_cuota, descuento]
     */
    public function run(): void
    {
        $rates = [
            [1, 'Tarifa Importada sin Valor', 101, 0, 0, 0, 0],
            // Biblia Grande (101)
            [101, '7 Cuota - Biblia $350.000', 101, 350000, 7, 50000, 0],
            [102, '10 Cuota - Biblia $350.000', 101, 350000, 10, 35000, 0],
            [103, '3 Cuota - Biblia $300.000', 101, 300000, 3, 100000, 50000],
            [104, '2 Cuota - Biblia $280.000', 101, 280000, 2, 140000, 70000],
            [105, '1 Cuota - Biblia $250.000', 101, 250000, 1, 250000, 100000],
            // Devocionario (102)
            [106, '7 Cuota - Devocionario $350.000', 102, 350000, 7, 50000, 0],
            [107, '10 Cuota - Devocionario $350.000', 102, 350000, 10, 35000, 0],
            [108, '3 Cuota - Devocionario $300.000', 102, 300000, 3, 100000, 50000],
            [109, '2 Cuota - Devocionario $280.000', 102, 280000, 2, 140000, 70000],
            [110, '1 Cuota - Devocionario $250.000', 102, 250000, 1, 250000, 100000],
            // Biblia Pequeña (103)
            [111, '3 Cuota - Biblia Pequeña $120.000', 103, 120000, 3, 40000, 0],
            [112, '2 Cuota - Biblia Pequeña $100.000', 103, 100000, 2, 50000, 20000],
            [113, '1 Cuota - Biblia Pequeña $90.000', 103, 90000, 1, 90000, 30000],
            // Atril de Pie (104)
            [114, '3 Cuota - Atril de Pie $150.000', 104, 150000, 3, 50000, 0],
            [115, '2 Cuota - Atril de Pie $120.000', 104, 120000, 2, 60000, 30000],
            [116, '1 Cuota - Atril de Pie $100.000', 104, 100000, 1, 100000, 50000],
            // Atril Pequeño (105)
            [117, '1 Cuota - Atril Pequeño $50.000', 105, 50000, 1, 50000, 0],
            // Virgen (106)
            [118, '1 Cuota - Estatua Virgen $30.000', 106, 30000, 1, 30000, 0],
        ];

        foreach ($rates as [$id, $name, $productId, $value, $installments, $installmentValue, $discount]) {
            Rate::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $name,
                    'product_id' => $productId,
                    'value' => $value,
                    'installments' => $installments,
                    'installment_value' => $installmentValue,
                    'discount' => $discount,
                    'active' => true,
                ],
            );
        }
    }
}
