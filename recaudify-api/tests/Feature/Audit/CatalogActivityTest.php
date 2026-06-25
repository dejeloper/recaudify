<?php

namespace Tests\Feature\Audit;

use App\Models\CallReason;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(["name" => "auditoria.ver", "guard_name" => "api"]);
        $role = Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());
        $this->user = User::factory()->withRole("superadmin")->create();
    }

    public function test_each_catalog_logs_creation_in_the_catalogos_log(): void
    {
        Seller::create(["name" => "Fabiola Guzmán", "username" => "Vendedor1"]);
        CallReason::create(["name" => "Programar Pago", "color" => "green"]);
        $product = Product::create(["name" => "Biblia Grande", "value" => 350000]);
        Rate::create([
            "name" => "7 Cuota",
            "product_id" => $product->id,
            "value" => 350000,
            "installments" => 7,
            "installment_value" => 50000,
        ]);

        $this->assertSame(4, Activity::where("log_name", "catalogos")->where("event", "created")->count());
    }

    public function test_seller_update_logs_only_changed_field(): void
    {
        $seller = Seller::create(["name" => "Fabiola", "username" => "v1"]);
        $seller->update(["name" => "Fabiola Guzmán"]);

        $activity = Activity::where("event", "updated")->latest("id")->first();

        $this->assertSame(["name"], array_keys($activity->attribute_changes["attributes"]));
    }

    public function test_activities_endpoint_resolves_model_label_per_catalog(): void
    {
        Seller::create(["name" => "Fabiola Guzmán", "username" => "Vendedor1"]);
        CallReason::create(["name" => "Programar Pago", "color" => "green"]);

        $response = $this->actingAs($this->user, "api")->getJson("/api/activities");

        $response
            ->assertStatus(200)
            ->assertJsonPath("data.0.model_label", "motivo de llamada")
            ->assertJsonPath("data.0.subject.label", "Programar Pago")
            ->assertJsonPath("data.1.model_label", "vendedor")
            ->assertJsonPath("data.1.subject.label", "Fabiola Guzmán");
    }
}
