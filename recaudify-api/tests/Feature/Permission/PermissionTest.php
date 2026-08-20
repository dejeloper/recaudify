<?php

namespace Tests\Feature\Permission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        "permissions.view",
        "permissions.create",
        "permissions.edit",
        "permissions.delete",
        "permissions.restore",
    ];

    public function test_index_lists_permissions(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/permissions")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_index_paginates_when_page_is_requested(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::create(["name" => "catalogos.ver", "guard_name" => "api"]);
        Permission::create(["name" => "catalogos.exportar", "guard_name" => "api"]);

        $this->getJson("/api/permissions?page=1&search=catalogos&per_page=1")
            ->assertStatus(200)
            ->assertJsonPath("data.meta.total", 2)
            ->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_search_when_paginated(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);
        Permission::create(["name" => "clientes.exportar", "guard_name" => "api"]);

        $this->getJson("/api/permissions?page=1&search=exportar")
            ->assertStatus(200)
            ->assertJsonPath("data.meta.total", 1)
            ->assertJsonPath("data.items.0.name", "clientes.exportar");
    }

    public function test_show_returns_permission_or_404(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $permission = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->getJson("/api/permissions/{$permission->id}")
            ->assertStatus(200)
            ->assertJsonPath("data.name", "clientes.ver");
        $this->getJson("/api/permissions/99999")->assertStatus(404);
    }

    public function test_store_creates_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/permissions", ["name" => "clientes.exportar"])->assertStatus(201);
        $this->assertDatabaseHas("permissions", ["name" => "clientes.exportar"]);
    }

    public function test_store_validates_name_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        // Sin punto module.action → inválido por regex.
        $this->postJson("/api/permissions", ["name" => "ClientesExportar"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_update_modifies_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $permission = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->putJson("/api/permissions/{$permission->id}", ["name" => "clientes.consultar"])->assertStatus(200);
        $this->assertDatabaseHas("permissions", ["id" => $permission->id, "name" => "clientes.consultar"]);
    }

    public function test_destroy_and_restore_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $permission = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->deleteJson("/api/permissions/{$permission->id}")->assertStatus(200);
        $this->assertSoftDeleted("permissions", ["id" => $permission->id]);

        $this->getJson("/api/permissions/trashed")->assertStatus(200);
        $this->postJson("/api/permissions/{$permission->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("permissions", ["id" => $permission->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/permissions")->assertStatus(401);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->postJson("/api/permissions", ["name" => "clientes.ver"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_store_validates_required_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/permissions", [])->assertStatus(422);
    }

    public function test_store_validates_name_max_length(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/permissions", ["name" => str_repeat("a", 101) . ".ver"])
            ->assertStatus(422);
    }

    public function test_update_validates_unique_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $perm = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);
        Permission::create(["name" => "clientes.editar", "guard_name" => "api"]);

        $this->putJson("/api/permissions/{$perm->id}", ["name" => "clientes.editar"])
            ->assertStatus(422);
    }

    public function test_update_allows_same_name_for_self(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $perm = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->putJson("/api/permissions/{$perm->id}", ["name" => "clientes.ver"])
            ->assertStatus(200);
    }

    public function test_update_validates_name_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $perm = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->putJson("/api/permissions/{$perm->id}", ["name" => "InvalidFormat"])
            ->assertStatus(422);
    }

    public function test_update_returns_404_for_unknown_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->putJson("/api/permissions/999", ["name" => "test.ok"])->assertStatus(404);
    }

    public function test_destroy_returns_404_for_unknown_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->deleteJson("/api/permissions/999")->assertStatus(404);
    }

    public function test_restore_returns_404_for_unknown_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/permissions/999/restore")->assertStatus(404);
    }

    public function test_restore_returns_404_for_non_trashed_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $perm = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);

        $this->postJson("/api/permissions/{$perm->id}/restore")->assertStatus(404);
    }

    public function test_trashed_lists_deleted_permissions(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $perm = Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);
        $this->deleteJson("/api/permissions/{$perm->id}")->assertStatus(200);
        Permission::create(["name" => "clientes.editar", "guard_name" => "api"]);

        $response = $this->getJson("/api/permissions/trashed")->assertStatus(200);
        $names = collect($response->json("data"))->pluck("name")->values()->all();
        $this->assertContains("clientes.ver", $names);
        $this->assertNotContains("clientes.editar", $names);
    }
}
