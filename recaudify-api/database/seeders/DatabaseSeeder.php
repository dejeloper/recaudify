<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

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
            MenuItemSeeder::class,
        ]);

        Cache::flush();
    }
}
