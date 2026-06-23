<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrador' => null, // all permissions

            'coordinador' => [
                'usuarios.ver', 'usuarios.crear', 'usuarios.editar',
                'roles.ver',
                'permisos.ver',
                'horarios.ver', 'horarios.crear', 'horarios.editar',
                'parametros.ver', 'parametros.crear', 'parametros.editar',
            ],

            'auxiliar' => [
                'usuarios.ver',
                'roles.ver',
                'permisos.ver',
                'horarios.ver',
                'parametros.ver',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }
        }

        $administrador = Role::findByName('administrador');
        $administrador->syncPermissions(Permission::pluck('name')->toArray());
    }
}
