<?php

namespace Tests\Unit\Services;

use App\Models\State;
use App\Models\StateTransition;
use App\Services\StateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StateServiceTest extends TestCase
{
    use RefreshDatabase;

    private StateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(StateService::class);
    }

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

    private function makeTransition(State $from, State $to): StateTransition
    {
        return StateTransition::create([
            "entity" => $from->entity,
            "from_state_id" => $from->id,
            "to_state_id" => $to->id,
        ]);
    }

    // ── create ────────────────────────────────────────────────────────

    public function test_create_persists_a_state(): void
    {
        $state = $this->service->create([
            "entity" => "contract",
            "key" => "review",
            "name" => "En revisión",
            "sort_order" => 1,
        ]);

        $this->assertDatabaseHas("states", ["id" => $state->id, "key" => "review"]);
    }

    public function test_create_with_is_initial_demotes_the_previous_one(): void
    {
        $old = $this->makeState(["key" => "draft", "is_initial" => true]);

        $new = $this->service->create([
            "entity" => "contract",
            "key" => "new_draft",
            "name" => "Nuevo borrador",
            "is_initial" => true,
            "sort_order" => 0,
        ]);

        $this->assertTrue($new->is_initial);
        $this->assertFalse($old->fresh()->is_initial);
    }

    public function test_create_without_is_initial_does_not_affect_others(): void
    {
        $old = $this->makeState(["key" => "draft", "is_initial" => true]);

        $this->service->create([
            "entity" => "contract",
            "key" => "active",
            "name" => "Activo",
            "is_initial" => false,
            "sort_order" => 1,
        ]);

        $this->assertTrue($old->fresh()->is_initial);
    }

    public function test_create_demote_only_affects_same_entity(): void
    {
        $otherEntityInitial = $this->makeState(["key" => "draft", "entity" => "client", "is_initial" => true]);

        $this->service->create([
            "entity" => "contract",
            "key" => "new_draft",
            "name" => "Nuevo borrador",
            "is_initial" => true,
            "sort_order" => 0,
        ]);

        $this->assertTrue($otherEntityInitial->fresh()->is_initial);
    }

    // ── update ────────────────────────────────────────────────────────

    public function test_update_changes_name(): void
    {
        $state = $this->makeState();

        $updated = $this->service->update($state, ["name" => "Renombrado"]);

        $this->assertSame("Renombrado", $updated->name);
    }

    public function test_update_with_is_initial_demotes_other_initials(): void
    {
        $old = $this->makeState(["key" => "draft", "is_initial" => true]);
        $state = $this->makeState(["key" => "active", "is_initial" => false, "sort_order" => 1]);

        $this->service->update($state, ["is_initial" => true]);

        $this->assertTrue($state->fresh()->is_initial);
        $this->assertFalse($old->fresh()->is_initial);
    }

    public function test_update_rejects_marking_final_with_outgoing_transitions(): void
    {
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished", "sort_order" => 1]);
        $this->makeTransition($from, $to);

        $this->expectException(ValidationException::class);
        $this->service->update($from, ["is_final" => true]);
    }

    public function test_update_allows_marking_final_without_outgoing_transitions(): void
    {
        $state = $this->makeState(["key" => "finished"]);

        $updated = $this->service->update($state, ["is_final" => true]);

        $this->assertTrue($updated->is_final);
    }

    public function test_update_is_final_check_runs_after_update(): void
    {
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished", "sort_order" => 1]);
        $this->makeTransition($from, $to);

        $this->expectException(ValidationException::class);
        $this->service->update($from, ["is_final" => true]);
    }

    // ── delete ────────────────────────────────────────────────────────

    public function test_delete_removes_state(): void
    {
        $state = $this->makeState();

        $this->service->delete($state);

        $this->assertSoftDeleted("states", ["id" => $state->id]);
    }

    public function test_delete_rejects_initial_state(): void
    {
        $state = $this->makeState(["is_initial" => true]);

        $this->expectException(ValidationException::class);
        $this->service->delete($state);
    }

    public function test_delete_rejects_state_referenced_as_origin(): void
    {
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished", "sort_order" => 1]);
        $this->makeTransition($from, $to);

        $this->expectException(ValidationException::class);
        $this->service->delete($from);
    }

    public function test_delete_rejects_state_referenced_as_destination(): void
    {
        $from = $this->makeState(["key" => "active"]);
        $to = $this->makeState(["key" => "finished", "sort_order" => 1]);
        $this->makeTransition($from, $to);

        $this->expectException(ValidationException::class);
        $this->service->delete($to);
    }

    // ── restore ───────────────────────────────────────────────────────

    public function test_restore_recovers_soft_deleted_state(): void
    {
        $state = $this->makeState();
        $state->delete();

        $restored = $this->service->restore($state);

        $this->assertNotSoftDeleted("states", ["id" => $restored->id]);
    }

    // ── queries ───────────────────────────────────────────────────────

    public function test_all_returns_states_ordered_by_entity_and_sort(): void
    {
        $this->makeState(["key" => "b", "sort_order" => 2, "entity" => "client"]);
        $this->makeState(["key" => "a", "sort_order" => 1, "entity" => "client"]);
        $this->makeState(["key" => "c", "sort_order" => 0, "entity" => "contract"]);

        $all = $this->service->all();

        $this->assertCount(3, $all);
        $this->assertSame("client", $all[0]->entity);
        $this->assertSame("a", $all[0]->key);
    }

    public function test_all_filters_by_entity(): void
    {
        $this->makeState(["key" => "draft", "entity" => "contract"]);
        $this->makeState(["key" => "active", "entity" => "client"]);

        $filtered = $this->service->all("client");

        $this->assertCount(1, $filtered);
        $this->assertSame("client", $filtered[0]->entity);
    }

    public function test_entities_returns_distinct_entity_keys(): void
    {
        $this->makeState(["entity" => "contract"]);
        $this->makeState(["entity" => "client"]);
        $this->makeState(["entity" => "contract", "key" => "active"]);

        $entities = $this->service->entities();

        $this->assertCount(2, $entities);
        $this->assertContains("contract", $entities);
        $this->assertContains("client", $entities);
    }

    public function test_find_returns_state_by_id(): void
    {
        $state = $this->makeState();

        $found = $this->service->find($state->id);

        $this->assertSame($state->id, $found->id);
    }

    public function test_find_returns_null_for_unknown_id(): void
    {
        $this->assertNull($this->service->find(9999));
    }

    public function test_trashed_returns_soft_deleted_states(): void
    {
        $state = $this->makeState();
        $state->delete();

        $trashed = $this->service->trashed();

        $this->assertCount(1, $trashed);
        $this->assertSame($state->id, $trashed[0]->id);
    }

    public function test_trashed_filters_by_entity(): void
    {
        $s1 = $this->makeState(["entity" => "contract"]);
        $s2 = $this->makeState(["entity" => "client", "key" => "active"]);
        $s1->delete();
        $s2->delete();

        $trashed = $this->service->trashed("client");

        $this->assertCount(1, $trashed);
        $this->assertSame("client", $trashed[0]->entity);
    }

    public function test_find_trashed_returns_soft_deleted_state(): void
    {
        $state = $this->makeState();
        $state->delete();

        $found = $this->service->findTrashed($state->id);

        $this->assertNotNull($found);
        $this->assertSame($state->id, $found->id);
    }
}
