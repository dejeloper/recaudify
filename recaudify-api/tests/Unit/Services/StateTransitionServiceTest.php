<?php

namespace Tests\Unit\Services;

use App\Models\State;
use App\Models\StateTransition;
use App\Services\StateTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StateTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    private StateTransitionService $service;

    private State $draft;
    private State $active;
    private State $finished;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(StateTransitionService::class);

        $this->draft = State::create([
            "entity" => "contract",
            "key" => "draft",
            "name" => "Borrador",
            "is_initial" => true,
            "sort_order" => 0,
        ]);

        $this->active = State::create([
            "entity" => "contract",
            "key" => "active",
            "name" => "Activo",
            "sort_order" => 1,
        ]);

        $this->finished = State::create([
            "entity" => "contract",
            "key" => "finished",
            "name" => "Finalizado",
            "is_final" => true,
            "sort_order" => 2,
        ]);
    }

    private function makeTransition(State $from, State $to, array $extra = []): StateTransition
    {
        return StateTransition::create(
            array_merge(
                [
                    "entity" => $from->entity,
                    "from_state_id" => $from->id,
                    "to_state_id" => $to->id,
                ],
                $extra,
            ),
        );
    }

    // ── create ────────────────────────────────────────────────────────

    public function test_create_persists_a_transition(): void
    {
        $transition = $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);

        $this->assertDatabaseHas("state_transitions", ["id" => $transition->id]);
        $this->assertSame($this->draft->id, $transition->from_state_id);
        $this->assertSame($this->active->id, $transition->to_state_id);
    }

    public function test_create_loads_relations(): void
    {
        $transition = $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);

        $this->assertNotNull($transition->fromState);
        $this->assertNotNull($transition->toState);
        $this->assertSame("draft", $transition->fromState->key);
        $this->assertSame("active", $transition->toState->key);
    }

    public function test_create_rejects_self_transition(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->draft->id,
        ]);
    }

    public function test_create_rejects_states_from_different_entities(): void
    {
        $otherEntityState = State::create([
            "entity" => "client",
            "key" => "lead",
            "name" => "Lead",
            "is_initial" => true,
            "sort_order" => 0,
        ]);

        $this->expectException(ValidationException::class);

        $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $otherEntityState->id,
        ]);
    }

    public function test_create_rejects_duplicate_transition(): void
    {
        $this->makeTransition($this->draft, $this->active);

        $this->expectException(ValidationException::class);

        $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);
    }

    public function test_create_rejects_origin_is_final(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->finished->id,
            "to_state_id" => $this->draft->id,
        ]);
    }

    public function test_create_allows_creation_transition(): void
    {
        $transition = $this->service->create([
            "entity" => "contract",
            "from_state_id" => null,
            "to_state_id" => $this->draft->id,
        ]);

        $this->assertNull($transition->from_state_id);
        $this->assertTrue($transition->isCreation());
    }

    public function test_create_same_key_different_entity_is_allowed(): void
    {
        $other = State::create([
            "entity" => "client",
            "key" => "draft",
            "name" => "Borrador",
            "is_initial" => true,
            "sort_order" => 0,
        ]);
        $otherActive = State::create([
            "entity" => "client",
            "key" => "active",
            "name" => "Activo",
            "sort_order" => 1,
        ]);

        $t1 = $this->service->create([
            "entity" => "contract",
            "from_state_id" => $this->draft->id,
            "to_state_id" => $this->active->id,
        ]);
        $t2 = $this->service->create([
            "entity" => "client",
            "from_state_id" => $other->id,
            "to_state_id" => $otherActive->id,
        ]);

        $this->assertNotSame($t1->id, $t2->id);
    }

    // ── update ────────────────────────────────────────────────────────

    public function test_update_changes_flags(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);

        $updated = $this->service->update($transition, [
            "requires_authorization" => true,
            "requires_reason" => true,
        ]);

        $this->assertTrue($updated->requires_authorization);
        $this->assertTrue($updated->requires_reason);
    }

    public function test_update_preserves_entity_and_states(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);

        $updated = $this->service->update($transition, ["label" => "Ir a activo"]);

        $this->assertSame("contract", $updated->entity);
        $this->assertSame($this->draft->id, $updated->from_state_id);
        $this->assertSame($this->active->id, $updated->to_state_id);
        $this->assertSame("Ir a activo", $updated->label);
    }

    public function test_update_allows_same_transition_to_itself(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);

        $updated = $this->service->update($transition, ["label" => "updated"]);

        $this->assertSame("updated", $updated->label);
    }

    public function test_update_rejects_duplicate_when_changing_states(): void
    {
        $this->makeTransition($this->draft, $this->active);
        $this->makeTransition($this->draft, $this->finished);
        $transition = $this->makeTransition($this->active, $this->finished);

        $this->expectException(ValidationException::class);

        $this->service->update($transition, [
            "from_state_id" => $this->draft->id,
        ]);
    }

    // ── delete ────────────────────────────────────────────────────────

    public function test_delete_removes_transition(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);

        $this->service->delete($transition);

        $this->assertSoftDeleted("state_transitions", ["id" => $transition->id]);
    }

    // ── restore ───────────────────────────────────────────────────────

    public function test_restore_recovers_soft_deleted_transition(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);
        $transition->delete();

        $restored = $this->service->restore($transition);

        $this->assertNotSoftDeleted("state_transitions", ["id" => $restored->id]);
    }

    // ── queries ───────────────────────────────────────────────────────

    public function test_all_returns_transitions_with_relations(): void
    {
        $this->makeTransition($this->draft, $this->active);

        $all = $this->service->all();

        $this->assertCount(1, $all);
        $this->assertNotNull($all[0]->fromState);
        $this->assertNotNull($all[0]->toState);
    }

    public function test_all_filters_by_entity(): void
    {
        $this->makeTransition($this->draft, $this->active);

        $other = State::create([
            "entity" => "client",
            "key" => "lead",
            "name" => "Lead",
            "is_initial" => true,
            "sort_order" => 0,
        ]);
        $otherTarget = State::create([
            "entity" => "client",
            "key" => "active",
            "name" => "Activo",
            "sort_order" => 1,
        ]);
        $this->makeTransition($other, $otherTarget);

        $filtered = $this->service->all("client");

        $this->assertCount(1, $filtered);
        $this->assertSame("client", $filtered[0]->entity);
    }

    public function test_find_returns_transition_by_id(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);

        $found = $this->service->find($transition->id);

        $this->assertSame($transition->id, $found->id);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->service->find(9999));
    }

    public function test_trashed_returns_soft_deleted_transitions(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);
        $transition->delete();

        $trashed = $this->service->trashed();

        $this->assertCount(1, $trashed);
        $this->assertSame($transition->id, $trashed[0]->id);
    }

    public function test_find_trashed_returns_soft_deleted_transition(): void
    {
        $transition = $this->makeTransition($this->draft, $this->active);
        $transition->delete();

        $found = $this->service->findTrashed($transition->id);

        $this->assertNotNull($found);
        $this->assertSame($transition->id, $found->id);
    }
}
