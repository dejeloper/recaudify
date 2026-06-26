<?php

namespace Tests\Unit\Services;

use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoleService();
        Permission::firstOrCreate(["name" => "clientes.ver", "guard_name" => "api"]);
        Permission::firstOrCreate(["name" => "clientes.crear", "guard_name" => "api"]);
    }

    public function test_create_with_permissions(): void
    {
        $role = $this->service->create("cobrador", ["clientes.ver"]);

        $this->assertSame("api", $role->guard_name);
        $this->assertTrue($role->hasPermissionTo("clientes.ver"));
    }

    public function test_update_only_name_keeps_permissions(): void
    {
        $role = $this->service->create("cobrador", ["clientes.ver"]);

        $this->service->update($role, "gestor", null); // permissions null → no toca permisos

        $this->assertSame("gestor", $role->fresh()->name);
        $this->assertTrue($role->fresh()->hasPermissionTo("clientes.ver"));
    }

    public function test_update_replaces_permissions(): void
    {
        $role = $this->service->create("cobrador", ["clientes.ver"]);

        $this->service->update($role, null, ["clientes.crear"]);

        $this->assertFalse($role->fresh()->hasPermissionTo("clientes.ver"));
        $this->assertTrue($role->fresh()->hasPermissionTo("clientes.crear"));
    }

    public function test_delete_clears_permissions_and_soft_deletes(): void
    {
        $role = $this->service->create("cobrador", ["clientes.ver"]);

        $this->service->delete($role);

        $this->assertSoftDeleted("roles", ["id" => $role->id]);
        $this->assertDatabaseMissing("role_has_permissions", ["role_id" => $role->id]);
    }

    public function test_all_only_returns_api_guard_roles(): void
    {
        Role::create(["name" => "web-role", "guard_name" => "web"]);
        $this->service->create("cobrador");

        $names = $this->service->all()->pluck("name")->all();

        $this->assertContains("cobrador", $names);
        $this->assertNotContains("web-role", $names);
    }
}
