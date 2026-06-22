<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Clientes
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'clientes.eliminar',
            'clientes.fusionar',

            // Contratos
            'contratos.ver',
            'contratos.crear',
            'contratos.editar',
            'contratos.cerrar',
            'contratos.cancelar',

            // Cartera
            'cartera.ver',

            // Cobranza
            'cobranza.ver',
            'cobranza.registrar-gestion',
            'cobranza.registrar-acuerdo',
            'cobranza.ver-historial',

            // Pagos
            'pagos.ver',
            'pagos.registrar',
            'pagos.anular',

            // Verificaciones
            'verificaciones.ver',
            'verificaciones.crear',
            'verificaciones.aprobar',
            'verificaciones.rechazar',

            // Documentos
            'documentos.ver',
            'documentos.subir',

            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.desactivar',
            'usuarios.restaurar',

            // Configuración
            'configuracion.ver',
            'configuracion.editar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
