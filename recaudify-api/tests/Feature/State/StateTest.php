<?php

namespace Tests\Feature\State;

use App\Models\State;
use App\Models\StateTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["states.view", "states.create", "states.edit", "states.delete", "states.restore"];

    private function makeState(array $attributes = []): State
    {
        return State::create(
            array_merge(
                [
                    "entity" => "contract",
                    "key" => "draft",
                    "name" => "Borrador",
                    "is_initial" => false,
                    "is_final" => false,
                    "sort_order" => 0,
                ],
                $attributes,
            ),
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/states")->assertStatus(401);
    }

    public function test_index_lists_states(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeState(["key" => "draft"]);
        $this->makeState(["key" => "active", "entity" => "client"]);

        $response = $this->getJson("/api/states")->assertStatus(200);

        $response->assertJsonCount(2, "data");
    }

    public function test_index_filters_by_entity(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeState(["key" => "draft", "entity" => "contract"]);
        $this->makeState(["key" => "active", "entity" => "client"]);

        $response = $this->getJson("/api/states?entity=client")->assertStatus(200);

        $response->assertJsonCount(1, "data");
        $response->assertJsonPath("data.0.entity", "client");
    }

    public function test_entities_lists_configured_entities(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeState(["key" => "draft", "entity" => "contract"]);
        $this->makeState(["key" => "active", "entity" => "client"]);

        $response = $this->getJson("/api/states/entities")->assertStatus(200);

        $response->assertJsonPath("data", ["client", "contract"]);
    }

    public function test_store_creates_state(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/states", [
            "entity" => "contract",
            "key" => "pending_validation",
            "name" => "Pendiente de validación",
            "color" => "#eab308",
            "sort_order" => 1,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.key", "pending_validation");
        $this->assertDatabaseHas("states", ["entity" => "contract", "key" => "pending_validation"]);
    }

    public function test_store_rejects_duplicated_key_for_same_entity(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeState(["key" => "draft"]);

        $this->postJson("/api/states", [
            "entity" => "contract",
            "key" => "draft",
            "name" => "Otro borrador",
        ])->assertStatus(422);
    }

    public function test_store_allows_same_key_on_a_different_entity(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeState(["key" => "draft", "entity" => "contract"]);

        $this->postJson("/api/states", [
            "entity" => "client",
            "key" => "draft",
            "name" => "Borrador",
        ])->assertStatus(201);
    }

    public function test_store_rejects_invalid_key_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/states", [
            "entity" => "contract",
            "key" => "Pendiente Validación",
            "name" => "Pendiente",
        ])->assertStatus(422);
    }

    public function test_marking_initial_demotes_the_previous_one(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $old = $this->makeState(["key" => "draft", "is_initial" => true]);

        $this->postJson("/api/states", [
            "entity" => "contract",
            "key" => "new_initial",
            "name" => "Nuevo inicial",
            "is_initial" => true,
        ])->assertStatus(201);

        $this->assertFalse($old->fresh()->is_initial);
    }

    public function test_update_changes_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $state = $this->makeState();

        $this->putJson("/api/states/{$state->id}", ["name" => "Renombrado"])->assertStatus(200);

        $this->assertEquals("Renombrado", $state->fresh()->name);
    }

    public function test_cannot_mark_as_final_a_state_with_outgoing_transitions(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished"]);
        StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $from->id,
            "to_state_id" => $to->id,
        ]);

        $this->putJson("/api/states/{$from->id}", ["is_final" => true])->assertStatus(422);
    }

    public function test_cannot_delete_the_initial_state(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $state = $this->makeState(["is_initial" => true]);

        $this->deleteJson("/api/states/{$state->id}")->assertStatus(422);
    }

    public function test_cannot_delete_a_state_used_by_a_transition(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished"]);
        StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $from->id,
            "to_state_id" => $to->id,
        ]);

        $this->deleteJson("/api/states/{$to->id}")->assertStatus(422);
    }

    public function test_delete_and_restore(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $state = $this->makeState();

        $this->deleteJson("/api/states/{$state->id}")->assertStatus(200);
        $this->assertSoftDeleted("states", ["id" => $state->id]);

        $this->getJson("/api/states/trashed")->assertStatus(200)->assertJsonCount(1, "data");

        $this->postJson("/api/states/{$state->id}/restore")->assertStatus(200);
        $this->assertNotSoftDeleted("states", ["id" => $state->id]);
    }

    public function test_show_returns_404_for_unknown_state(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/states/9999")->assertStatus(404);
    }
}
