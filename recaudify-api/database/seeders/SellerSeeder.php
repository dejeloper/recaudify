<?php

namespace Database\Seeders;

use App\Models\Seller;
use Illuminate\Database\Seeder;

class SellerSeeder extends Seeder
{
    /**
     * Vendedores del catálogo legacy (códigos 101–102 preservados).
     */
    public function run(): void
    {
        $sellers = [
            [101, 'Fabiola Guzmán', 'Vendedor1'],
            [102, 'Hector Gómez', 'Vendedor2'],
        ];

        foreach ($sellers as [$id, $name, $username]) {
            Seller::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'username' => $username, 'active' => true],
            );
        }
    }
}
