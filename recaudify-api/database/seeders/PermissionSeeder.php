<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            "users.view",
            "users.create",
            "users.edit",
            "users.deactivate",
            "users.restore",

            // Roles
            "roles.view",
            "roles.create",
            "roles.edit",
            "roles.delete",
            "roles.restore",

            // Permissions
            "permissions.view",
            "permissions.create",
            "permissions.edit",
            "permissions.delete",
            "permissions.restore",

            // Schedules
            "schedules.view",
            "schedules.create",
            "schedules.edit",
            "schedules.delete",
            "schedules.restore",

            // Parameters
            "parameters.view",
            "parameters.create",
            "parameters.edit",
            "parameters.delete",
            "parameters.restore",

            // Catalogs (products, rates, sellers, call reasons, states)
            "catalogs.view",
            "catalogs.create",
            "catalogs.edit",
            "catalogs.delete",
            "catalogs.restore",

            // Audit (activity feed)
            "audit.view",

            // Access (login audit)
            "access.view",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission]);
        }
    }
}
