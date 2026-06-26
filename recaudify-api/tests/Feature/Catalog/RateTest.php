<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use App\Models\Rate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

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
        $this->product = Product::create(["name" => "Biblia Grande", "value" => 350000]);
    }

    private function rateData(array $overrides = []): array
    {
        return array_merge(
            [
                "name" => "7 Cuota - Biblia",
                "product_id" => $this->product->id,
                "value" => 350000,
                "installments" => 7,
                "installment_value" => 50000,
                "discount" => 0,
            ],
            $overrides,
        );
    }

    public function test_index_lists_rates(): void
    {
        Rate::create($this->rateData());
        Rate::create(
            $this->rateData(["name" => "10 Cuota - Biblia", "installments" => 10, "installment_value" => 35000]),
        );

        $response = $this->actingAs($this->user, "api")->getJson("/api/rates");

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, "data")
            ->assertJsonPath("data.0.product.name", "Biblia Grande");
    }

    public function test_store_creates_rate(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/rates", $this->rateData());

        $response->assertStatus(201)->assertJsonPath("data.installments", 7);
        $this->assertDatabaseHas("rates", ["name" => "7 Cuota - Biblia", "value" => 350000]);
    }

    public function test_store_validates_product_exists(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson(
            "/api/rates",
            $this->rateData(["product_id" => 9999]),
        );

        $response->assertStatus(422)->assertJsonStructure(["data" => ["product_id"]]);
    }

    public function test_update_modifies_rate(): void
    {
        $rate = Rate::create($this->rateData());

        $response = $this->actingAs($this->user, "api")->putJson(
            "/api/rates/{$rate->id}",
            $this->rateData(["installment_value" => 60000]),
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas("rates", ["id" => $rate->id, "installment_value" => 60000]);
    }

    public function test_destroy_and_restore_rate(): void
    {
        $rate = Rate::create($this->rateData());

        $this->actingAs($this->user, "api")
            ->deleteJson("/api/rates/{$rate->id}")
            ->assertStatus(200);
        $this->assertSoftDeleted("rates", ["id" => $rate->id]);

        $this->actingAs($this->user, "api")
            ->postJson("/api/rates/{$rate->id}/restore")
            ->assertStatus(200);
        $this->assertDatabaseHas("rates", ["id" => $rate->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/rates")->assertStatus(401);
    }
}
