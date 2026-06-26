<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["users.view", "users.create", "users.edit", "users.deactivate", "users.restore"];

    public function test_index_lists_users(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        User::factory()->count(2)->create();

        $this->getJson("/api/users")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_show_returns_user_or_404(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["username" => "jperez"]);

        $this->getJson("/api/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath("data.username", "jperez");
        $this->getJson("/api/users/99999")->assertStatus(404);
    }

    public function test_store_creates_user_with_role(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Role::firstOrCreate(["name" => "cobrador", "guard_name" => "api"]);

        $response = $this->postJson("/api/users", [
            "name" => "Juan Pérez",
            "username" => "jperez",
            "password" => "secret1234",
            "password_confirmation" => "secret1234",
            "role" => "cobrador",
        ]);

        $response->assertStatus(201)->assertJsonPath("data.username", "jperez");
        $this->assertDatabaseHas("users", ["username" => "jperez"]);
        $this->assertTrue(User::where("username", "jperez")->first()->hasRole("cobrador"));
    }

    public function test_store_validates_username_format_and_confirmation(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/users", [
            "name" => "Juan Pérez",
            "username" => "Juan Perez", // espacios/mayúsculas → regex inválido
            "password" => "secret1234",
            "password_confirmation" => "otracosa", // no confirma
        ])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["username", "password"]]);
    }

    public function test_update_modifies_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["name" => "Viejo Nombre"]);

        $this->putJson("/api/users/{$user->id}", ["name" => "Nuevo Nombre"])->assertStatus(200);
        $this->assertDatabaseHas("users", ["id" => $user->id, "name" => "Nuevo Nombre"]);
    }

    public function test_destroy_disables_and_restore_reactivates(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();

        $this->deleteJson("/api/users/{$user->id}")->assertStatus(200);
        $this->assertSoftDeleted("users", ["id" => $user->id]);

        $this->getJson("/api/users/trashed/{$user->id}")->assertStatus(200);
        $this->postJson("/api/users/{$user->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("users", ["id" => $user->id, "deleted_at" => null]);
    }

    public function test_index_disabled_lists_trashed_users(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();
        $user->delete();

        $this->getJson("/api/users/disabled")->assertStatus(200)->assertJsonCount(1, "data");
    }

    public function test_search_by_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        User::factory()->create(["name" => "Fabiola Guzmán"]);

        $this->getJson("/api/users/search/Fabiola")->assertStatus(200)->assertJsonCount(1, "data");
    }

    public function test_sync_permissions_assign_and_revoke(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::firstOrCreate(["name" => "clientes.ver", "guard_name" => "api"]);
        $user = User::factory()->create();

        $this->postJson("/api/users/{$user->id}/permissions", [
            "permissions" => ["clientes.ver"],
            "assign" => true,
        ])->assertStatus(200);
        $this->assertTrue($user->fresh()->hasPermissionTo("clientes.ver"));

        $this->postJson("/api/users/{$user->id}/permissions", [
            "permissions" => ["clientes.ver"],
            "assign" => false,
        ])->assertStatus(200);
        $this->assertFalse($user->fresh()->hasPermissionTo("clientes.ver"));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/users")->assertStatus(401);
    }
}
