<?php

namespace Tests\Feature\User;

use App\Enums\ParameterType;
use App\Models\Branch;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        "users.view",
        "users.create",
        "users.edit",
        "users.deactivate",
        "users.restore",
        "users.reset-password",
    ];

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

    public function test_reset_password_generates_random_password_by_default(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();

        $response = $this->postJson("/api/users/{$user->id}/reset-password")->assertStatus(200);
        $password = $response->json("data.password");

        $this->assertNotEmpty($password);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
    }

    public function test_reset_password_uses_fixed_value_when_configured(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();

        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Authentication->value, "key" => "reset_password_mode"],
            ["value" => "fixed", "cast" => "string"],
        );
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Authentication->value, "key" => "reset_password_fixed_value"],
            ["value" => "ClaveFija123", "cast" => "string"],
        );

        $response = $this->postJson("/api/users/{$user->id}/reset-password")->assertStatus(200);

        $response->assertJsonPath("data.password", "ClaveFija123");
        $this->assertTrue(Hash::check("ClaveFija123", $user->fresh()->password));
    }

    public function test_reset_password_returns_404_for_unknown_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/users/99999/reset-password")->assertStatus(404);
    }

    public function test_store_assigns_branch_id(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = Branch::create(["code" => "BOG", "name" => "Bogotá", "is_main" => true]);
        Role::firstOrCreate(["name" => "cobrador", "guard_name" => "api"]);

        $response = $this->postJson("/api/users", [
            "name" => "Juan Pérez",
            "username" => "jperez",
            "password" => "secret1234",
            "password_confirmation" => "secret1234",
            "role" => "cobrador",
            "branch_id" => $branch->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas("users", ["username" => "jperez", "branch_id" => $branch->id]);
    }

    public function test_store_rejects_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/users", [
            "name" => "Juan Pérez",
            "username" => "jperez",
            "password" => "secret1234",
            "password_confirmation" => "secret1234",
            "branch_id" => 999,
        ])->assertStatus(422)->assertJsonStructure(["data" => ["branch_id"]]);
    }

    public function test_update_modifies_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = Branch::create(["code" => "BOG", "name" => "Bogotá", "is_main" => true]);
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", ["branch_id" => $branch->id])->assertStatus(200);
        $this->assertDatabaseHas("users", ["id" => $user->id, "branch_id" => $branch->id]);
    }

    public function test_update_rejects_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", ["branch_id" => 999])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["branch_id"]]);
    }

    public function test_show_includes_branch_data(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = Branch::create(["code" => "BOG", "name" => "Bogotá", "is_main" => true]);
        $user = User::factory()->create(["branch_id" => $branch->id]);

        $response = $this->getJson("/api/users/{$user->id}")->assertStatus(200);

        $response->assertJsonPath("data.branch_id", $branch->id);
        $response->assertJsonPath("data.branch.code", "BOG");
    }

    public function test_store_rejects_duplicate_username(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        User::factory()->create(["username" => "jperez"]);

        $this->postJson("/api/users", [
            "name" => "Otro Juan",
            "username" => "jperez",
            "password" => "secret1234",
            "password_confirmation" => "secret1234",
        ])->assertStatus(422)->assertJsonStructure(["data" => ["username"]]);
    }

    public function test_store_rejects_missing_password_confirmation(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/users", [
            "name" => "Juan",
            "username" => "jperez",
            "password" => "secret1234",
        ])->assertStatus(422)->assertJsonStructure(["data" => ["password"]]);
    }

    public function test_update_does_not_require_password(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["name" => "Viejo"]);

        $this->putJson("/api/users/{$user->id}", ["name" => "Nuevo"])->assertStatus(200);
        $this->assertDatabaseHas("users", ["id" => $user->id, "name" => "Nuevo"]);
    }

    public function test_destroy_returns_404_for_unknown_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->deleteJson("/api/users/99999")->assertStatus(404);
    }

    public function test_restore_returns_404_for_unknown_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/users/99999/restore")->assertStatus(404);
    }

    public function test_trashed_returns_404_for_unknown_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/users/trashed/99999")->assertStatus(404);
    }

    public function test_sync_permissions_returns_404_for_unknown_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::firstOrCreate(["name" => "test.perm", "guard_name" => "api"]);

        $this->postJson("/api/users/99999/permissions", [
            "permissions" => ["test.perm"],
            "assign" => true,
        ])->assertStatus(404);
    }
}
