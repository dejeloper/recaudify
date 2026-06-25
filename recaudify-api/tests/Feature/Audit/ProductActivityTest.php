<?php

namespace Tests\Feature\Audit;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (["catalogos.crear", "catalogos.editar", "catalogos.eliminar", "auditoria.ver"] as $permission) {
            Permission::create(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());

        $this->user = User::factory()
            ->withRole("superadmin")
            ->create(["name" => "Jhonatan"]);
    }

    public function test_creating_a_product_via_api_records_activity_with_causer(): void
    {
        $this->actingAs($this->user, "api")
            ->postJson("/api/products", ["name" => "Biblia Grande", "value" => 350000])
            ->assertStatus(201);

        $this->assertDatabaseHas("activity_log", [
            "log_name" => "catalogos",
            "event" => "created",
            "description" => "creó",
            "causer_id" => $this->user->id,
        ]);
    }

    public function test_activities_endpoint_returns_readable_payload(): void
    {
        $product = Product::create(["name" => "Biblia Grande", "value" => 350000]);
        $product->update(["value" => 300000]);

        $response = $this->actingAs($this->user, "api")->getJson("/api/activities?model=Product");

        $response
            ->assertStatus(200)
            ->assertJsonPath("data.items.0.event", "updated")
            ->assertJsonPath("data.items.0.description", "actualizó")
            ->assertJsonPath("data.items.0.model_label", "producto")
            ->assertJsonPath("data.items.0.subject.label", "Biblia Grande")
            ->assertJsonPath("data.items.0.changes.0.field", "value")
            ->assertJsonPath("data.items.0.changes.0.old", 350000)
            ->assertJsonPath("data.items.0.changes.0.new", 300000);
    }

    public function test_update_logs_only_changed_fields(): void
    {
        $product = Product::create(["name" => "Biblia Grande", "value" => 350000]);
        $product->update(["value" => 300000]);

        $activity = Activity::where("event", "updated")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame(["value"], array_keys($activity->attribute_changes["attributes"]));
    }

    public function test_activities_endpoint_requires_permission(): void
    {
        $this->getJson("/api/activities")->assertStatus(401);
    }
}
