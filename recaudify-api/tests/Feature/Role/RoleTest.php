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
}
