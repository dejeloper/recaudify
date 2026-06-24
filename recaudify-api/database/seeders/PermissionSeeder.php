<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Usuarios
            "usuarios.ver",
            "usuarios.crear",
            "usuarios.editar",
            "usuarios.desactivar",
            "usuarios.restaurar",

            // Roles
            "roles.ver",
            "roles.crear",
            "roles.editar",
            "roles.eliminar",
            "roles.restaurar",

            // Permisos
            "permisos.ver",
            "permisos.crear",
            "permisos.editar",
            "permisos.eliminar",
            "permisos.restaurar",

            // Horarios
            "horarios.ver",
            "horarios.crear",
            "horarios.editar",
            "horarios.eliminar",
            "horarios.restaurar",

            // Parámetros
            "parametros.ver",
            "parametros.crear",
            "parametros.editar",
            "parametros.eliminar",
            "parametros.restaurar",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission]);
        }
    }
}
