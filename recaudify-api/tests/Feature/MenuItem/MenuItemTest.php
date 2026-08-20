<?php

namespace Tests\Feature\MenuItem;

use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["menu.view", "menu.create", "menu.edit", "menu.delete", "menu.restore"];

    public function test_index_lists_menu_items(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        MenuItem::create(["label" => "Configuración"]);

        $this->getJson("/api/menu-items")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_mine_returns_only_active_items_as_a_tree(): void
    {
        $this->authenticateWith();
        $group = MenuItem::create(["label" => "Grupo", "is_active" => true]);
        MenuItem::create(["parent_id" => $group->id, "label" => "Activo", "is_active" => true]);
        MenuItem::create(["parent_id" => $group->id, "label" => "Inactivo", "is_active" => false]);

        $response = $this->getJson("/api/menu")->assertStatus(200);

        $children = $response->json("data.0.children");
        $this->assertCount(1, $children);
        $this->assertSame("Activo", $children[0]["label"]);
    }

    public function test_store_creates_menu_item(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/menu-items", [
            "label" => "Clientes",
            "route" => "/admin/clients",
            "order" => 0,
        ]);

        $response->assertStatus(201)->assertJsonPath("data.label", "Clientes");
        $this->assertDatabaseHas("menu_items", ["label" => "Clientes"]);
    }

    public function test_store_validates_permission_exists(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/menu-items", [
            "label" => "Clientes",
            "permission" => "no.existe",
        ])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["permission"]]);
    }

    public function test_rejects_more_than_three_levels_of_depth(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $root = MenuItem::create(["label" => "Grupo"]);
        $child = MenuItem::create(["parent_id" => $root->id, "label" => "Ítem"]);
        $grandchild = MenuItem::create(["parent_id" => $child->id, "label" => "Sub-ítem"]);

        $this->postJson("/api/menu-items", [
            "parent_id" => $grandchild->id,
            "label" => "Cuarto nivel",
        ])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["parent_id"]]);
    }

    public function test_update_modifies_menu_item(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes", "order" => 0]);

        $this->putJson("/api/menu-items/{$item->id}", ["order" => 5])->assertStatus(200);
        $this->assertDatabaseHas("menu_items", ["id" => $item->id, "order" => 5]);
    }

    public function test_destroy_and_restore_menu_item(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes"]);

        $this->deleteJson("/api/menu-items/{$item->id}")->assertStatus(200);
        $this->assertSoftDeleted("menu_items", ["id" => $item->id]);

        $this->getJson("/api/menu-items/trashed")->assertStatus(200);
        $this->postJson("/api/menu-items/{$item->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("menu_items", ["id" => $item->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/menu-items")->assertStatus(401);
    }

    public function test_show_returns_menu_item(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes", "route" => "/admin/clients"]);

        $response = $this->getJson("/api/menu-items/{$item->id}")->assertStatus(200);

        $response->assertJsonPath("data.label", "Clientes");
        $response->assertJsonPath("data.route", "/admin/clients");
    }

    public function test_show_returns_404_for_unknown(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/menu-items/999")->assertStatus(404);
    }

    public function test_store_validates_required_label(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/menu-items", ["route" => "/admin/test"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["label"]]);
    }

    public function test_store_validates_parent_exists(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/menu-items", [
            "label" => "Hijo",
            "parent_id" => 999,
        ])->assertStatus(422)->assertJsonStructure(["data" => ["parent_id"]]);
    }

    public function test_update_rejects_self_parent(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Grupo"]);

        $this->putJson("/api/menu-items/{$item->id}", ["parent_id" => $item->id])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["parent_id"]]);
    }

    public function test_update_rejects_depth_violation(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $root = MenuItem::create(["label" => "Grupo"]);
        $child = MenuItem::create(["parent_id" => $root->id, "label" => "Ítem"]);
        $grandchild = MenuItem::create(["parent_id" => $child->id, "label" => "Sub-ítem"]);

        $this->putJson("/api/menu-items/{$child->id}", ["parent_id" => $grandchild->id])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["parent_id"]]);
    }

    public function test_update_validates_permission_exists(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes"]);

        $this->putJson("/api/menu-items/{$item->id}", ["permission" => "no.existe"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["permission"]]);
    }

    public function test_update_returns_404_for_unknown(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->putJson("/api/menu-items/999", ["label" => "Test"])->assertStatus(404);
    }

    public function test_delete_returns_404_for_unknown(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->deleteJson("/api/menu-items/999")->assertStatus(404);
    }

    public function test_restore_returns_404_for_unknown(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/menu-items/999/restore")->assertStatus(404);
    }

    public function test_restore_returns_404_for_non_trashed(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes"]);

        $this->postJson("/api/menu-items/{$item->id}/restore")->assertStatus(404);
    }

    public function test_trashed_lists_deleted_items(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes"]);
        $item->delete();
        MenuItem::create(["label" => "Proveedores"]);

        $this->getJson("/api/menu-items/trashed")->assertStatus(200)->assertJsonCount(1, "data");
    }

    public function test_store_creates_with_parent(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parent = MenuItem::create(["label" => "Grupo"]);

        $response = $this->postJson("/api/menu-items", [
            "label" => "Hijo",
            "parent_id" => $parent->id,
        ]);

        $response->assertStatus(201)->assertJsonPath("data.parent_id", $parent->id);
    }

    public function test_mine_excludes_inactive_parent_groups(): void
    {
        $this->authenticateWith();
        $group = MenuItem::create(["label" => "Grupo Inactivo", "is_active" => false]);
        MenuItem::create(["parent_id" => $group->id, "label" => "Hijo", "is_active" => true]);

        $response = $this->getJson("/api/menu")->assertStatus(200);

        $this->assertCount(0, $response->json("data"));
    }

    public function test_update_modifies_label(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Viejo"]);

        $this->putJson("/api/menu-items/{$item->id}", ["label" => "Nuevo"])->assertStatus(200);
        $this->assertDatabaseHas("menu_items", ["id" => $item->id, "label" => "Nuevo"]);
    }

    public function test_update_toggles_is_active(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $item = MenuItem::create(["label" => "Clientes", "is_active" => true]);

        $this->putJson("/api/menu-items/{$item->id}", ["is_active" => false])->assertStatus(200);
        $this->assertDatabaseHas("menu_items", ["id" => $item->id, "is_active" => false]);
    }
}
