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
                "users.view",
                "users.create",
                "users.edit",
                "roles.view",
                "permissions.view",
                "schedules.view",
                "schedules.create",
                "schedules.edit",
                "parameters.view",
                "parameters.create",
                "parameters.edit",
                "branches.view",
                "branches.view-all",
            ],

            "auxiliar" => [
                "users.view",
                "roles.view",
                "permissions.view",
                "schedules.view",
                "parameters.view",
                "branches.view",
            ],

            // Corre las tareas automáticas. Solo los permisos que el cron necesita.
            "sistema" => ["audit.purge"],

            // Roles operativos del negocio. Sus permisos se completan cuando exista cada módulo;
            // se crean desde ya porque el modelo de datos y las transiciones ya los referencian.
            "gestor" => ["catalogs.view", "branches.view"],
            "recaudador" => ["catalogs.view", "branches.view"],
            "vendedor" => ["catalogs.view", "branches.view"],
            "cerrador" => ["catalogs.view", "branches.view"],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(["name" => $roleName]);

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }
        }

        $allPermissions = Permission::pluck("name")->toArray();
        $adminPermissions = array_values(array_diff($allPermissions, ["sessions.view", "sessions.revoke"]));

        Role::findByName("superadmin")->syncPermissions($allPermissions);
        Role::findByName("administrador")->syncPermissions($adminPermissions);
    }
}
