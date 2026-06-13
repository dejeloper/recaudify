<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrador' => null, // all permissions assigned in UserSeeder

            'supervisor' => [
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar', 'clientes.fusionar',
                'contratos.ver', 'contratos.crear', 'contratos.editar', 'contratos.cerrar', 'contratos.cancelar',
                'cartera.ver',
                'cobranza.ver', 'cobranza.registrar-gestion', 'cobranza.registrar-acuerdo', 'cobranza.ver-historial',
                'pagos.ver', 'pagos.registrar', 'pagos.anular',
                'verificaciones.ver', 'verificaciones.crear', 'verificaciones.aprobar', 'verificaciones.rechazar',
                'documentos.ver', 'documentos.subir',
                'usuarios.ver',
                'configuracion.ver',
            ],

            'verificador' => [
                'clientes.ver',
                'contratos.ver',
                'cartera.ver',
                'verificaciones.ver', 'verificaciones.crear', 'verificaciones.aprobar', 'verificaciones.rechazar',
                'documentos.ver', 'documentos.subir',
            ],

            'vendedor' => [
                'clientes.ver', 'clientes.crear', 'clientes.editar',
                'contratos.ver', 'contratos.crear', 'contratos.editar',
                'cartera.ver',
                'documentos.ver', 'documentos.subir',
            ],

            'cobrador' => [
                'clientes.ver',
                'contratos.ver',
                'cartera.ver',
                'cobranza.ver', 'cobranza.registrar-gestion', 'cobranza.registrar-acuerdo', 'cobranza.ver-historial',
                'pagos.ver', 'pagos.registrar',
                'documentos.ver', 'documentos.subir',
            ],

            'auxiliar' => [
                'clientes.ver',
                'contratos.ver',
                'cartera.ver',
                'pagos.ver',
                'documentos.ver',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }
        }

        // Administrador gets every permission
        $administrador = Role::findByName('administrador', 'api');
        $administrador->syncPermissions(
            \Spatie\Permission\Models\Permission::where('guard_name', 'api')->pluck('name')->toArray()
        );
    }
}
