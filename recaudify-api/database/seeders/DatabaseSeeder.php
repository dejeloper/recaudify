<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            UserScheduleSeeder::class,
            ParameterSeeder::class,
            // Catálogos base (Fase 1)
            StateSeeder::class,
            ProductSeeder::class,
            RateSeeder::class,
            SellerSeeder::class,
            CallReasonSeeder::class,
        ]);
    }
}
