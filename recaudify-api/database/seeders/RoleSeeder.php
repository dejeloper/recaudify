<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            "superadmin" => null, // all permissions, exempt from schedules
            "administrador" => null, // all permissions

            "coordinador" => [
                "usuarios.ver",
                "usuarios.crear",
                "usuarios.editar",
                "roles.ver",
                "permisos.ver",
                "horarios.ver",
                "horarios.crear",
                "horarios.editar",
                "parametros.ver",
                "parametros.crear",
                "parametros.editar",
                "catalogos.ver",
                "catalogos.crear",
                "catalogos.editar",
            ],

            "auxiliar" => [
                "usuarios.ver",
                "roles.ver",
                "permisos.ver",
                "horarios.ver",
                "parametros.ver",
                "catalogos.ver",
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(["name" => $roleName]);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }
        }

        $allPermissions = Permission::pluck("name")->toArray();

        Role::findByName("superadmin")->syncPermissions($allPermissions);
        Role::findByName("administrador")->syncPermissions($allPermissions);
    }
}
