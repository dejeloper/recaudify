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
                "catalogs.view",
                "catalogs.create",
                "catalogs.edit",
            ],

            "auxiliar" => [
                "users.view",
                "roles.view",
                "permissions.view",
                "schedules.view",
                "parameters.view",
                "catalogs.view",
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
