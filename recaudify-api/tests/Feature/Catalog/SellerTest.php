<?php

namespace Tests\Feature\Catalog;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            ["catalogos.ver", "catalogos.crear", "catalogos.editar", "catalogos.eliminar", "catalogos.restaurar"]
            as $permission
        ) {
            Permission::create(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());

        $this->user = User::factory()->withRole("superadmin")->create();
    }

    public function test_index_lists_sellers(): void
    {
        Seller::create(["name" => "Fabiola Guzmán", "username" => "Vendedor1"]);
        Seller::create(["name" => "Hector Gómez", "username" => "Vendedor2"]);

        $response = $this->actingAs($this->user, "api")->getJson("/api/sellers");

        $response->assertStatus(200)->assertJsonCount(2, "data");
    }

    public function test_store_creates_seller(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/sellers", [
            "name" => "Nuevo Vendedor",
            "username" => "Vendedor3",
        ]);

        $response->assertStatus(201)->assertJsonPath("data.name", "Nuevo Vendedor");
        $this->assertDatabaseHas("sellers", ["name" => "Nuevo Vendedor", "username" => "Vendedor3"]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/sellers", []);

        $response->assertStatus(422)->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_update_modifies_seller(): void
    {
        $seller = Seller::create(["name" => "Viejo", "username" => "v"]);

        $response = $this->actingAs($this->user, "api")->putJson("/api/sellers/{$seller->id}", [
            "name" => "Actualizado",
            "username" => "v",
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas("sellers", ["id" => $seller->id, "name" => "Actualizado"]);
    }

    public function test_destroy_and_restore_seller(): void
    {
        $seller = Seller::create(["name" => "Temporal"]);

        $this->actingAs($this->user, "api")
            ->deleteJson("/api/sellers/{$seller->id}")
            ->assertStatus(200);
        $this->assertSoftDeleted("sellers", ["id" => $seller->id]);

        $this->actingAs($this->user, "api")
            ->postJson("/api/sellers/{$seller->id}/restore")
            ->assertStatus(200);
        $this->assertDatabaseHas("sellers", ["id" => $seller->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/sellers")->assertStatus(401);
    }
}
