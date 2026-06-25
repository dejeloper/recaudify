<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea los permisos indicados, un rol superadmin con todos ellos y un usuario
     * autenticado vía guard `api`. Superadmin además omite el chequeo de horario.
     */
    protected function authenticateWith(array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::firstOrCreate(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());

        $user = User::factory()->withRole("superadmin")->create();
        $this->actingAs($user, "api");

        return $user;
    }
}
