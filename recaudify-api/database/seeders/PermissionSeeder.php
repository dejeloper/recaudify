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
            "users.reset-password",

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

            // Activity model log (Spatie)
            "audit.view",

            // Login audit
            "access.view",

            // Sessions
            "sessions.view",
            "sessions.revoke",

            // Catalogs
            "catalogs.view",

            // Menu builder
            "menu.view",
            "menu.create",
            "menu.edit",
            "menu.delete",
            "menu.restore",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission]);
        }
    }
}
