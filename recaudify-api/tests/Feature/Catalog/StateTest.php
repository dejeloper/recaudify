<?php

namespace Tests\Feature\Catalog;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(["name" => "catalogos.ver", "guard_name" => "api"]);

        $role = Role::create(["name" => "superadmin", "guard_name" => "api"]);
        $role->syncPermissions(Permission::all());

        $this->user = User::factory()->withRole("superadmin")->create();

        $this->makeState(111, "Al día", "contract");
        $this->makeState(123, "Paz y Salvo", "client");
        $this->makeState(104, "Al día", "client");
    }

    private function makeState(int $id, string $name, string $type): void
    {
        $state = new State(["name" => $name, "state_type" => $type]);
        $state->id = $id;
        $state->save();
    }

    public function test_index_lists_all_states(): void
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/states");

        $response->assertStatus(200)->assertJsonCount(3, "data");
    }

    public function test_index_filters_by_type(): void
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/states?type=client");

        $response->assertStatus(200)->assertJsonCount(2, "data");
    }

    public function test_show_returns_state(): void
    {
        $response = $this->actingAs($this->user, "api")->getJson("/api/states/111");

        $response
            ->assertStatus(200)
            ->assertJsonPath("data.name", "Al día")
            ->assertJsonPath("data.state_type", "contract");
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/states")->assertStatus(401);
    }
}
