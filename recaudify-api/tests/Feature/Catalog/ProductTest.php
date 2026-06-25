<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            ['catalogos.ver', 'catalogos.crear', 'catalogos.editar', 'catalogos.eliminar', 'catalogos.restaurar'] as $permission
        ) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
        $role->syncPermissions(Permission::all());

        $this->user = User::factory()->withRole('superadmin')->create();
    }

    public function test_index_lists_products(): void
    {
        Product::create(['name' => 'Biblia Grande', 'value' => 350000]);
        Product::create(['name' => 'Virgen', 'value' => 30000]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/products');

        $response->assertStatus(200)->assertJsonPath('success', true)->assertJsonCount(2, 'data');
    }

    public function test_show_returns_product(): void
    {
        $product = Product::create(['name' => 'Devocionario', 'value' => 350000]);

        $response = $this->actingAs($this->user, 'api')->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)->assertJsonPath('data.name', 'Devocionario')->assertJsonPath('data.value', 350000);
    }

    public function test_store_creates_product(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/products', [
            'name' => 'Atril de Pie',
            'value' => 150000,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.name', 'Atril de Pie');
        $this->assertDatabaseHas('products', ['name' => 'Atril de Pie', 'value' => 150000]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')->postJson('/api/products', []);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['data' => ['name', 'value']]);
    }

    public function test_update_modifies_product(): void
    {
        $product = Product::create(['name' => 'Atril', 'value' => 50000]);

        $response = $this->actingAs($this->user, 'api')->putJson("/api/products/{$product->id}", [
            'name' => 'Atril Pequeño',
            'value' => 60000,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Atril Pequeño', 'value' => 60000]);
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $product = Product::create(['name' => 'Temporal', 'value' => 1000]);

        $response = $this->actingAs($this->user, 'api')->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_trashed_and_restore_product(): void
    {
        $product = Product::create(['name' => 'Restaurable', 'value' => 2000]);
        $product->delete();

        $this->actingAs($this->user, 'api')->getJson('/api/products/trashed')->assertStatus(200)->assertJsonCount(
            1,
            'data',
        );

        $this->actingAs($this->user, 'api')->postJson("/api/products/{$product->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }
}
