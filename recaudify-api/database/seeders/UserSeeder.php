<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => null,
                'password' => 'superadmin1234',
                'role' => 'superadmin',
            ],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'email' => null,
                'password' => 'admin1234',
                'role' => 'administrador',
            ],
            [
                'name' => 'Coordinador',
                'username' => 'coordinador',
                'email' => null,
                'password' => 'admin1234',
                'role' => 'coordinador',
            ],
            [
                'name' => 'Auxiliar',
                'username' => 'auxiliar',
                'email' => null,
                'password' => 'admin1234',
                'role' => 'auxiliar',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['username' => $data['username']], $data);

            $user->assignRole($role);
        }
    }
}
