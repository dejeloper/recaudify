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

            // Activity model log (Spatie)
            "audit.view",

            // Login audit
            "access.view",
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission]);
        }
    }
}
