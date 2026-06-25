<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

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

            // Catálogos (productos, tarifas, vendedores, motivos de llamada, estados)
            "catalogos.ver",
            "catalogos.crear",
            "catalogos.editar",
            "catalogos.eliminar",
            "catalogos.restaurar",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission]);
        }
    }
}
