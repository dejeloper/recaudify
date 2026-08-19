<?php

namespace Tests\Unit\Services;

use App\Exceptions\StateTransitionException;
use App\Models\Activity;
use App\Models\Concerns\HasState;
use App\Models\Permission;
use App\Models\Role;
use App\Models\State;
use App\Models\StateTransition;
use App\Models\User;
use App\Services\StateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/** Modelo mínimo de prueba: el motor no depende de ninguna entidad de negocio concreta. */
class Order extends Model
{
    use HasState;

    protected $table = "test_orders";

    protected $fillable = ["name", "state_id"];

    public function stateEntity(): string
    {
        return "order";
    }
}

class StateMachineTest extends TestCase
{
    use RefreshDatabase;

    private StateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create("test_orders", function ($table) {
            $table->id();
            $table->string("name")->nullable();
            $table->foreignId("state_id")->nullable();
            $table->timestamps();
        });

        $this->machine = app(StateMachine::class);
        $this->seedLifecycle();
    }

    private function seedLifecycle(): void
    {
        $states = [
            ["draft", true, false],
            ["active", false, false],
            ["suspended", false, false],
            ["finished", false, true],
        ];

        foreach ($states as $order => [$key, $initial, $final]) {
            State::create([
                "entity" => "order",
                "key" => $key,
                "name" => ucfirst($key),
                "is_initial" => $initial,
                "is_final" => $final,
                "sort_order" => $order,
            ]);
        }

        $this->makeTransition("draft", "active");
        $this->makeTransition("active", "suspended", requiresReason: true, requiresAuthorization: true);
        $this->makeTransition("active", "finished", automatic: true);
        $this->makeTransition("draft", "finished", permission: "orders.finish");
    }

    private function makeTransition(
        string $from,
        string $to,
        ?string $permission = null,
        bool $automatic = false,
        bool $requiresAuthorization = false,
        bool $requiresReason = false,
    ): StateTransition {
        return StateTransition::create([
            "entity" => "order",
            "from_state_id" => State::where("entity", "order")->where("key", $from)->value("id"),
            "to_state_id" => State::where("entity", "order")->where("key", $to)->value("id"),
            "permission" => $permission,
            "is_automatic" => $automatic,
            "requires_authorization" => $requiresAuthorization,
            "requires_reason" => $requiresReason,
        ]);
    }

    private function makeOrder(string $stateKey = "draft"): Order
    {
        return Order::create([
            "name" => "pedido",
            "state_id" => State::where("entity", "order")->where("key", $stateKey)->value("id"),
        ]);
    }

    private function userWithPermission(?string $permission): User
    {
        if ($permission) {
            Permission::firstOrCreate(["name" => $permission, "guard_name" => "api"]);
        }

        $role = Role::firstOrCreate(["name" => "operativo", "guard_name" => "api"]);
        $role->syncPermissions(array_filter([$permission]));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return User::factory()->withRole("operativo")->create();
    }

    public function test_initial_state_is_the_one_flagged(): void
    {
        $this->assertEquals("draft", $this->machine->initialState("order")->key);
    }

    public function test_initial_state_fails_when_entity_has_none(): void
    {
        $this->expectException(StateTransitionException::class);
        $this->machine->initialState("unknown_entity");
    }

    public function test_applies_a_declared_transition(): void
    {
        $order = $this->makeOrder("draft");

        $this->machine->apply($order, "active");

        $this->assertEquals("active", $order->fresh()->stateKey());
    }

    public function test_rejects_a_transition_that_is_not_declared(): void
    {
        $order = $this->makeOrder("suspended");

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "finished");
    }

    public function test_rejects_unknown_state(): void
    {
        $order = $this->makeOrder("draft");

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "no_existe");
    }

    public function test_transition_requiring_reason_fails_without_one(): void
    {
        $order = $this->makeOrder("active");

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "suspended", authorized: true);
    }

    public function test_transition_requiring_authorization_fails_without_it(): void
    {
        $order = $this->makeOrder("active");

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "suspended", reason: "el cliente viaja 2 meses");
    }

    public function test_transition_succeeds_with_reason_and_authorization(): void
    {
        $order = $this->makeOrder("active");

        $this->machine->apply($order, "suspended", reason: "el cliente viaja 2 meses", authorized: true);

        $this->assertEquals("suspended", $order->fresh()->stateKey());
    }

    public function test_automatic_transition_cannot_be_run_by_hand(): void
    {
        $order = $this->makeOrder("active");

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "finished");
    }

    public function test_automatic_transition_runs_for_the_engine(): void
    {
        $order = $this->makeOrder("active");

        $this->machine->apply($order, "finished", automatic: true);

        $this->assertEquals("finished", $order->fresh()->stateKey());
    }

    public function test_transition_respects_permission(): void
    {
        $order = $this->makeOrder("draft");
        $user = $this->userWithPermission(null);

        $this->expectException(StateTransitionException::class);
        $this->machine->apply($order, "finished", user: $user);
    }

    public function test_transition_allowed_with_permission(): void
    {
        $order = $this->makeOrder("draft");
        $user = $this->userWithPermission("orders.finish");

        $this->machine->apply($order, "finished", user: $user);

        $this->assertEquals("finished", $order->fresh()->stateKey());
    }

    public function test_available_transitions_exclude_automatic_ones(): void
    {
        $order = $this->makeOrder("active");

        $keys = $this->machine->availableTransitions($order)->pluck("toState.key")->all();

        $this->assertEquals(["suspended"], $keys);
    }

    public function test_available_transitions_filter_by_user_permission(): void
    {
        $order = $this->makeOrder("draft");
        $user = $this->userWithPermission(null);

        $keys = $this->machine->availableTransitions($order, $user)->pluck("toState.key")->all();

        $this->assertEquals(["active"], $keys);
    }

    public function test_can_reports_whether_a_move_is_possible(): void
    {
        $order = $this->makeOrder("draft");

        $this->assertTrue($this->machine->can($order, "active"));
        $this->assertFalse($this->machine->can($order, "suspended"));
    }

    public function test_state_change_is_audited_with_reason(): void
    {
        $order = $this->makeOrder("active");

        $this->machine->apply($order, "suspended", reason: "el cliente viaja 2 meses", authorized: true);

        $activity = Activity::query()->where("log_name", "estados")->where("event", "state_changed")->firstOrFail();

        $this->assertEquals("cambió el estado", $activity->description);
        $this->assertEquals("active", $activity->properties["old"]["state"]);
        $this->assertEquals("suspended", $activity->properties["attributes"]["state"]);
        $this->assertEquals("el cliente viaja 2 meses", $activity->getExtraProperty("reason"));
    }

    public function test_final_state_has_no_way_out(): void
    {
        $order = $this->makeOrder("finished");

        $this->assertTrue($order->isFinalState());
        $this->assertCount(0, $this->machine->availableTransitions($order));
    }

    public function test_in_state_helpers(): void
    {
        $order = $this->makeOrder("active");

        $this->assertTrue($order->isInState("active", "suspended"));
        $this->assertFalse($order->isInState("draft"));
        $this->assertEquals(1, Order::query()->inState("active")->count());
    }
}
