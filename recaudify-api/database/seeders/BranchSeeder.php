<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                "code" => "PRINCIPAL",
                "name" => "Sede Principal",
                "address" => null,
                "city" => null,
                "phone" => null,
                "email" => null,
                "is_main" => true,
                "sort_order" => 0,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(["code" => $branch["code"]], $branch);
        }
    }
}
