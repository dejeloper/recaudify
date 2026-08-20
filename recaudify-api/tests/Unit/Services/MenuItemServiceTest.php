<?php

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuItemServiceTest extends TestCase
{
    use RefreshDatabase;

    private MenuItemService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(MenuItemService::class);
    }

    private function createMenuItem(array $overrides = []): MenuItem
    {
        return MenuItem::create(array_merge([
            "label" => "Test Item",
            "order" => 0,
            "is_active" => true,
        ], $overrides));
    }

    public function test_create_root_item(): void
    {
        $item = $this->service->create(["label" => "Inicio", "order" => 1, "is_active" => true]);

        $this->assertNull($item->parent_id);
        $this->assertSame("Inicio", $item->label);
    }

    public function test_create_with_parent(): void
    {
        $parent = $this->createMenuItem(["label" => "Catálogos"]);
        $child = $this->service->create(["label" => "Clientes", "parent_id" => $parent->id, "order" => 1, "is_active" => true]);

        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_create_rejects_depth_violation(): void
    {
        $level1 = $this->createMenuItem(["label" => "L1"]);
        $level2 = $this->createMenuItem(["label" => "L2", "parent_id" => $level1->id]);
        $level3 = $this->createMenuItem(["label" => "L3", "parent_id" => $level2->id]);

        $this->expectException(ValidationException::class);
        $this->service->create(["label" => "L4", "parent_id" => $level3->id]);
    }

    public function test_update_rejects_self_parent(): void
    {
        $item = $this->createMenuItem(["label" => "Solo"]);

        $this->expectException(ValidationException::class);
        $this->service->update($item, ["parent_id" => $item->id]);
    }

    public function test_update_rejects_depth_violation(): void
    {
        $level1 = $this->createMenuItem(["label" => "L1"]);
        $level2 = $this->createMenuItem(["label" => "L2", "parent_id" => $level1->id]);
        $level3 = $this->createMenuItem(["label" => "L3", "parent_id" => $level2->id]);
        $sibling = $this->createMenuItem(["label" => "S1", "parent_id" => $level1->id]);

        $this->expectException(ValidationException::class);
        $this->service->update($sibling, ["parent_id" => $level3->id]);
    }

    public function test_update_label(): void
    {
        $item = $this->createMenuItem(["label" => "Viejo"]);

        $result = $this->service->update($item, ["label" => "Nuevo"]);

        $this->assertSame("Nuevo", $result->label);
    }

    public function test_update_reorder(): void
    {
        $item = $this->createMenuItem(["order" => 1]);

        $result = $this->service->update($item, ["order" => 99]);

        $this->assertSame(99, $result->order);
    }

    public function test_delete_soft_deletes(): void
    {
        $item = $this->createMenuItem();

        $this->service->delete($item);

        $this->assertSoftDeleted("menu_items", ["id" => $item->id]);
    }

    public function test_restore_recovers_item(): void
    {
        $item = $this->createMenuItem();
        $item->delete();

        $restored = $this->service->restore($item);

        $this->assertNull($restored->deleted_at);
    }

    public function test_find_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->find(999));
    }

    public function test_update_same_parent_does_not_validate_depth(): void
    {
        $level1 = $this->createMenuItem(["label" => "L1"]);
        $level2 = $this->createMenuItem(["label" => "L2", "parent_id" => $level1->id]);

        $result = $this->service->update($level2, ["parent_id" => $level1->id, "label" => "L2 Updated"]);

        $this->assertSame("L2 Updated", $result->label);
    }
}
