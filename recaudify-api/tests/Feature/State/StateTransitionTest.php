<?php

namespace Tests\Feature\State;

use App\Models\Permission;
use App\Models\State;
use App\Models\StateTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTransitionTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["states.view", "states.create", "states.edit", "states.delete", "states.restore"];

    private State $draft;
    private State $active;
    private State $finished;

    protected function setUp(): void
    {
        parent::setUp();

        $this->draft = $this->makeState("draft", 0);
        $this->active = $this->makeState("active", 1);
        $this->finished = $this->makeState("finished", 2, isFinal: true);
    }

    private function makeState(string $key, int $order, string $entity = "contract", bool $isFinal = false): State
    {
        return State::create([
            "entity" => $entity,
            "key" => $key,
            "name" => ucfirst($key),
            "is_final" => $isFinal,
            "sort_order" => $order,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/state-transitions")->assertStatus(401);
    }

    public function test_store_creates_transition(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
            "requires_reason" => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.from.key", "draft");
        $response->assertJsonPath("data.to.key", "active");
        $response->assertJsonPath("data.requires_reason", true);
    }

    public function test_store_creation_transition_without_origin(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => null,
            "to_state_id" => $this->draft->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.is_creation", true);
        $response->assertJsonPath("data.from", null);
    }

    public function test_rejects_transition_to_the_same_state(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->draft->id,
        ])->assertStatus(422);
    }

    public function test_rejects_duplicated_transition(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ])->assertStatus(422);
    }

    public function test_rejects_states_from_another_entity(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $otherEntity = $this->makeState("open", 0, entity: "commitment");

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $otherEntity->id,
        ])->assertStatus(422);
    }

    public function test_rejects_outgoing_transition_from_a_final_state(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->finished->id,
            "to_state_id" => $this->active->id,
        ])->assertStatus(422);
    }

    public function test_rejects_unknown_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
            "permission" => "no.existe",
        ])->assertStatus(422);
    }

    public function test_accepts_existing_permission(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Permission::firstOrCreate(["name" => "contracts.validate", "guard_name" => "api"]);

        $this->postJson("/api/state-transitions", [
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
            "permission" => "contracts.validate",
        ])->assertStatus(201);
    }

    public function test_index_filters_by_entity(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);
        $commitmentState = $this->makeState("open", 0, entity: "commitment");
        StateTransition::create([
            "entity" => "commitment",
            "from_state_id" => null,
            "to_state_id" => $commitmentState->id,
        ]);

        $response = $this->getJson("/api/state-transitions?entity=commitment")->assertStatus(200);

        $response->assertJsonCount(1, "data");
        $response->assertJsonPath("data.0.entity", "commitment");
    }

    public function test_update_changes_flags(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $transition = StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);

        $this->putJson("/api/state-transitions/{$transition->id}", [
            "requires_authorization" => true,
            "requires_reason" => true,
        ])->assertStatus(200);

        $fresh = $transition->fresh();
        $this->assertTrue($fresh->requires_authorization);
        $this->assertTrue($fresh->requires_reason);
    }

    public function test_delete_and_restore(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $transition = StateTransition::create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);

        $this->deleteJson("/api/state-transitions/{$transition->id}")->assertStatus(200);
        $this->assertSoftDeleted("state_transitions", ["id" => $transition->id]);

        $this->postJson("/api/state-transitions/{$transition->id}/restore")->assertStatus(200);
        $this->assertNotSoftDeleted("state_transitions", ["id" => $transition->id]);
    }

    public function test_show_returns_404_for_unknown_transition(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/state-transitions/9999")->assertStatus(404);
    }
}
