<?php

namespace Tests\Feature\Role;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["roles.view", "roles.create", "roles.edit", "roles.delete", "roles.restore"];

    public function test_index_lists_roles(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->getJson("/api/roles")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_index_paginates_when_page_is_requested(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Role::create(["name" => "cobrador", "guard_name" => "api"]);
        Role::create(["name" => "vendedor", "guard_name" => "api"]);

        $this->getJson("/api/roles?page=1&per_page=1")
            ->assertStatus(200)
            ->assertJsonPath("data.meta.total", 2)
            ->assertJsonCount(1, "data.items");
    }

    public function test_index_filters_by_search_when_paginated(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Role::create(["name" => "cobrador", "guard_name" => "api"]);
        Role::create(["name" => "vendedor", "guard_name" => "api"]);

        $this->getJson("/api/roles?page=1&search=cobra")
            ->assertStatus(200)
            ->assertJsonPath("data.meta.total", 1)
            ->assertJsonPath("data.items.0.name", "cobrador");
    }

    public function test_show_returns_role_or_404(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->getJson("/api/roles/{$role->id}")
            ->assertStatus(200)
            ->assertJsonPath("data.name", "cobrador");
        $this->getJson("/api/roles/99999")->assertStatus(404);
    }

    public function test_store_creates_role_with_permissions(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::firstOrCreate(["name" => "clientes.ver", "guard_name" => "api"]);

        $response = $this->postJson("/api/roles", [
            "name" => "cobrador",
            "permissions" => ["clientes.ver"],
        ]);

        $response->assertStatus(201)->assertJsonPath("data.name", "cobrador");
        $this->assertDatabaseHas("roles", ["name" => "cobrador"]);
    }

    public function test_store_validates_unique_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->postJson("/api/roles", ["name" => "cobrador"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_update_modifies_role(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->putJson("/api/roles/{$role->id}", ["name" => "gestor"])->assertStatus(200);
        $this->assertDatabaseHas("roles", ["id" => $role->id, "name" => "gestor"]);
    }

    public function test_destroy_and_restore_role(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->deleteJson("/api/roles/{$role->id}")->assertStatus(200);
        $this->assertSoftDeleted("roles", ["id" => $role->id]);

        $this->getJson("/api/roles/trashed")->assertStatus(200);
        $this->postJson("/api/roles/{$role->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("roles", ["id" => $role->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/roles")->assertStatus(401);
    }

    public function test_store_validates_permissions_exist(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/roles", [
            "name" => "cobrador",
            "permissions" => ["no.existe.permiso"],
        ]);

        $response->assertStatus(422);
        $data = $response->json("data");
        $this->assertNotEmpty($data);
    }

    public function test_update_modifies_permissions(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);
        Permission::firstOrCreate(["name" => "clientes.ver", "guard_name" => "api"]);
        Permission::firstOrCreate(["name" => "clientes.editar", "guard_name" => "api"]);

        $this->putJson("/api/roles/{$role->id}", [
            "permissions" => ["clientes.ver", "clientes.editar"],
        ])->assertStatus(200);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo("clientes.ver"));
        $this->assertTrue($role->hasPermissionTo("clientes.editar"));
    }

    public function test_store_validates_required_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/roles", [])->assertStatus(422)
            ->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_trashed_lists_deleted_roles(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);
        $this->deleteJson("/api/roles/{$role->id}")->assertStatus(200);
        Role::create(["name" => "vendedor", "guard_name" => "api"]);

        $response = $this->getJson("/api/roles/trashed")->assertStatus(200);
        $names = collect($response->json("data"))->pluck("name")->values()->all();
        $this->assertContains("cobrador", $names);
        $this->assertNotContains("vendedor", $names);
    }

    public function test_restore_returns_404_for_unknown(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/roles/999/restore")->assertStatus(404);
    }

    public function test_restore_returns_404_for_non_trashed(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);

        $this->postJson("/api/roles/{$role->id}/restore")->assertStatus(404);
    }

    public function test_update_returns_404_for_unknown_role(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->putJson("/api/roles/999", ["name" => "test"])->assertStatus(404);
    }

    public function test_delete_returns_404_for_unknown_role(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->deleteJson("/api/roles/999")->assertStatus(404);
    }
}
