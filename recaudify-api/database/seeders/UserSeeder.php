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
                'name'     => 'Administrador',
                'username' => 'admin',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'administrador',
            ],
            [
                'name'     => 'Supervisor',
                'username' => 'supervisor',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'supervisor',
            ],
            [
                'name'     => 'Verificador',
                'username' => 'verificador',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'verificador',
            ],
            [
                'name'     => 'Vendedor',
                'username' => 'vendedor',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'vendedor',
            ],
            [
                'name'     => 'Cobrador',
                'username' => 'cobrador',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'cobrador',
            ],
            [
                'name'     => 'Auxiliar',
                'username' => 'auxiliar',
                'email'    => null,
                'password' => 'admin1234',
                'role'     => 'auxiliar',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(
                ['username' => $data['username']],
                $data
            );

            $user->assignRole($role);
        }

        // Extra random users per role for realistic dev data
        $roles = ['supervisor', 'verificador', 'vendedor', 'cobrador', 'auxiliar'];

        foreach ($roles as $role) {
            User::factory(3)->withRole($role)->create();
        }
    }
}
