<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Productos del catálogo legacy (códigos 101–106 preservados).
     */
    public function run(): void
    {
        $products = [
            [101, 'Biblia Grande', 350000],
            [102, 'Devocionario', 350000],
            [103, 'Biblia Pequeña', 120000],
            [104, 'Atril de Pie', 150000],
            [105, 'Atril Pequeño', 50000],
            [106, 'Virgen', 30000],
        ];

        foreach ($products as [$id, $name, $value]) {
            Product::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'value' => $value, 'active' => true],
            );
        }
    }
}
