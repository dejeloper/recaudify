<?php

namespace Tests\Feature\Catalog;

use App\Models\CallReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CallReasonTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            ["catalogs.view", "catalogs.create", "catalogs.edit", "catalogs.delete", "catalogs.restore"]
            as $permission
        ) {
            Permission::create(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());

        $this->user = User::factory()->withRole("superadmin")->create();
    }

    public function test_index_lists_call_reasons(): void
    {
        CallReason::create(["name" => "Programar Pago", "color" => "green"]);
        CallReason::create(["name" => "Cliente no Paga", "color" => "red"]);

        $response = $this->actingAs($this->user, "api")->getJson("/api/call-reasons");

        $response->assertStatus(200)->assertJsonCount(2, "data");
    }

    public function test_store_creates_call_reason(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/call-reasons", [
            "name" => "Llamar otro día",
            "color" => "black",
        ]);

        $response->assertStatus(201)->assertJsonPath("data.color", "black");
        $this->assertDatabaseHas("call_reasons", ["name" => "Llamar otro día", "color" => "black"]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, "api")->postJson("/api/call-reasons", []);

        $response->assertStatus(422)->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_update_modifies_call_reason(): void
    {
        $reason = CallReason::create(["name" => "Viejo", "color" => "gray"]);

        $response = $this->actingAs($this->user, "api")->putJson("/api/call-reasons/{$reason->id}", [
            "name" => "Actualizado",
            "color" => "blue",
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas("call_reasons", ["id" => $reason->id, "name" => "Actualizado", "color" => "blue"]);
    }

    public function test_destroy_and_restore_call_reason(): void
    {
        $reason = CallReason::create(["name" => "Temporal"]);

        $this->actingAs($this->user, "api")
            ->deleteJson("/api/call-reasons/{$reason->id}")
            ->assertStatus(200);
        $this->assertSoftDeleted("call_reasons", ["id" => $reason->id]);

        $this->actingAs($this->user, "api")
            ->postJson("/api/call-reasons/{$reason->id}/restore")
            ->assertStatus(200);
        $this->assertDatabaseHas("call_reasons", ["id" => $reason->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/call-reasons")->assertStatus(401);
    }
}
