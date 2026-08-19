<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                "name" => "Super Admin",
                "username" => "superadmin",
                "email" => "superadmin@recaudify.test",
                "password" => "superadmin1234",
                "role" => "superadmin",
            ],
            [
                "name" => "Administrador",
                "username" => "admin",
                "email" => "admin@recaudify.test",
                "password" => "admin1234",
                "role" => "administrador",
            ],
            [
                "name" => "Coordinador",
                "username" => "coordinador",
                "email" => "coordinador@recaudify.test",
                "password" => "admin1234",
                "role" => "coordinador",
            ],
            [
                "name" => "Auxiliar",
                "username" => "auxiliar",
                "email" => "auxiliar@recaudify.test",
                "password" => "admin1234",
                "role" => "auxiliar",
            ],
        ];

        $this->seedUsers($users);
        $this->seedSystemUser();
    }

    private function seedUsers(array $users): void
    {
        foreach ($users as $data) {
            $role = $data["role"];
            unset($data["role"]);

            $user = User::firstOrCreate(["username" => $data["username"]], $data);

            $user->assignRole($role);
        }
    }

    /**
     * Autor de las tareas automáticas.
     *
     * Nace inactivo y con una contraseña aleatoria que nadie conoce: no está pensado para que una
     * persona entre con él, sino para firmar lo que hace el cron.
     */
    private function seedSystemUser(): void
    {
        $user = User::firstOrCreate(
            ["username" => User::SYSTEM_USERNAME],
            [
                "name" => "Sistema",
                "email" => null,
                "password" => Str::random(64),
                "active" => false,
            ],
        );

        $user->assignRole("sistema");
    }
}
