<?php

namespace Tests\Unit\Services;

use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionService();
    }

    public function test_create_uses_api_guard(): void
    {
        $permission = $this->service->create("clientes.ver");

        $this->assertSame("api", $permission->guard_name);
        $this->assertDatabaseHas("permissions", ["name" => "clientes.ver", "guard_name" => "api"]);
    }

    public function test_update_renames_permission(): void
    {
        $permission = $this->service->create("clientes.ver");

        $this->service->update($permission, "clientes.consultar");

        $this->assertDatabaseHas("permissions", ["id" => $permission->id, "name" => "clientes.consultar"]);
    }

    public function test_delete_restore_and_trashed(): void
    {
        $permission = $this->service->create("clientes.ver");

        $this->service->delete($permission);
        $this->assertSoftDeleted("permissions", ["id" => $permission->id]);
        $this->assertCount(1, $this->service->trashed());

        $this->service->restore($this->service->findTrashed($permission->id));
        $this->assertCount(0, $this->service->trashed());
    }

    public function test_all_only_returns_api_guard_permissions(): void
    {
        $this->service->create("clientes.ver");
        Permission::create(["name" => "clientes.ver", "guard_name" => "web"]);

        $this->assertCount(1, $this->service->all());
    }
}
