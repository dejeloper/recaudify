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
}
