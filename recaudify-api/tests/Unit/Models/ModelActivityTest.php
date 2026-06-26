<?php

namespace Tests\Unit\Models;

use App\Models\CallReason;
use App\Models\Concerns\LogsModelActivity;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Role;
use App\Models\Seller;
use App\Models\State;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\LogOptions;
use Tests\TestCase;

class ModelActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_trait_returns_log_options(): void
    {
        $model = new class {
            use LogsModelActivity;

            protected function activitylogFields(): array
            {
                return ["name"];
            }

            protected function logName(): string
            {
                return "test_log";
            }
        };

        $options = $model->getActivitylogOptions();

        $this->assertInstanceOf(LogOptions::class, $options);
    }

    public function test_user_creation_logs_activity_with_correct_log_name(): void
    {
        $user = User::factory()->create();

        $activity = Activity::inLog("usuarios")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("usuarios", $activity->log_name);
        $this->assertEquals($user->id, $activity->subject_id);
        $this->assertEquals(User::class, $activity->subject_type);
    }

    public function test_user_creation_has_creo_description(): void
    {
        User::factory()->create();

        $activity = Activity::inLog("usuarios")->latest()->first();

        $this->assertEquals("creó", $activity->description);
    }

    public function test_user_update_logs_only_dirty_fields(): void
    {
        $user = User::factory()->create(["name" => "Original"]);

        $user->update(["name" => "Actualizado"]);

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();
        $this->assertCount(
            2,
            $activities,
            "Expected 2 activities (create + update), got: " . $activities->pluck("description", "id")->toJson(),
        );

        $activity = $activities->last();

        $this->assertEquals("actualizó", $activity->description);

        $changes = $activity->attribute_changes;
        $this->assertNotNull($changes);
        $this->assertArrayHasKey("old", $changes->toArray());
        $this->assertArrayHasKey("attributes", $changes->toArray());
        $this->assertEquals("Original", $changes["old"]["name"]);
        $this->assertEquals("Actualizado", $changes["attributes"]["name"]);
    }

    public function test_user_update_logs_only_configured_fields(): void
    {
        $user = User::factory()->create(["name" => "Original", "username" => "original"]);

        $user->update(["name" => "Nuevo", "username" => "nuevo"]);

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();
        $this->assertCount(
            2,
            $activities,
            "Expected 2 activities (create + update), got: " . $activities->pluck("description", "id")->toJson(),
        );

        $activity = $activities->last();
        $changes = $activity->attribute_changes;
        $this->assertNotNull($changes);
        $this->assertArrayHasKey("attributes", $changes->toArray());
        $attributes = $changes["attributes"];

        $this->assertArrayHasKey("name", $attributes);
        $this->assertArrayHasKey("username", $attributes);
        $this->assertArrayNotHasKey("email", $attributes, "email was not dirty, should not appear with logOnlyDirty");
        $this->assertArrayNotHasKey("active", $attributes, "active was not dirty, should not appear with logOnlyDirty");
        $this->assertArrayNotHasKey("password", $attributes, "password is not in configured fields");
    }

    public function test_user_update_without_changes_does_not_log(): void
    {
        $user = User::factory()->create(["name" => "Igual"]);
        $countBefore = Activity::inLog("usuarios")->count();

        $user->update(["name" => "Igual"]);

        $countAfter = Activity::inLog("usuarios")->count();
        $this->assertEquals($countBefore, $countAfter);
    }

    public function test_user_soft_delete_logs_activity(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();
        $this->assertCount(
            2,
            $activities,
            "Expected 2 activities (create + delete), got: " . $activities->pluck("description", "id")->toJson(),
        );

        $activity = $activities->last();
        $this->assertEquals("eliminó", $activity->description);
    }

    public function test_user_restore_logs_activity(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $user->restore();

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();
        $this->assertCount(
            3,
            $activities,
            "Expected 3 activities (create + delete + restore), got: " .
                $activities->pluck("description", "id")->toJson(),
        );

        $activity = $activities->last();
        $this->assertEquals("restauró", $activity->description);
    }

    public function test_activity_logs_authenticated_causer(): void
    {
        /** @var User $causer */
        $causer = User::factory()->create();
        $this->actingAs($causer, "api");

        User::factory()->create();

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();

        $activity = $activities->last();
        $this->assertEquals($causer->id, $activity->causer_id);
    }

    public function test_parameter_creation_logs_to_catalogos(): void
    {
        $parameter = Parameter::create([
            "key" => "shift-status",
            "value" => "true",
            "description" => "Control de horarios",
        ]);

        $activity = Activity::inLog("catalogos")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($parameter->id, $activity->subject_id);
        $this->assertEquals(Parameter::class, $activity->subject_type);
        $this->assertEquals("creó", $activity->description);
    }

    public function test_state_creation_logs_to_catalogos(): void
    {
        $state = State::create([
            "name" => "Activo",
            "state_type" => "client",
            "active" => true,
        ]);

        $activity = Activity::inLog("catalogos")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($state->id, $activity->subject_id);
        $this->assertEquals(State::class, $activity->subject_type);
    }

    public function test_permission_creation_logs_to_seguridad(): void
    {
        $permission = Permission::create(["name" => "test.view", "guard_name" => "api"]);

        $activity = Activity::inLog("seguridad")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("seguridad", $activity->log_name);
        $this->assertEquals($permission->id, $activity->subject_id);
        $this->assertEquals(Permission::class, $activity->subject_type);
    }

    public function test_role_creation_logs_to_seguridad(): void
    {
        $role = Role::create(["name" => "test-role", "guard_name" => "api"]);

        $activity = Activity::inLog("seguridad")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("seguridad", $activity->log_name);
        $this->assertEquals($role->id, $activity->subject_id);
        $this->assertEquals(Role::class, $activity->subject_type);
    }

    public function test_product_creation_logs_to_catalogos(): void
    {
        $product = Product::create([
            "name" => "Test Product",
            "value" => 5000,
            "active" => true,
        ]);

        $activity = Activity::inLog("catalogos")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($product->id, $activity->subject_id);
        $this->assertEquals(Product::class, $activity->subject_type);
        $this->assertEquals("creó", $activity->description);
    }

    public function test_seller_creation_logs_to_catalogos(): void
    {
        $seller = Seller::create([
            "name" => "Test Seller",
            "username" => "seller01",
            "active" => true,
        ]);

        $activity = Activity::inLog("catalogos")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($seller->id, $activity->subject_id);
        $this->assertEquals(Seller::class, $activity->subject_type);
        $this->assertEquals("creó", $activity->description);
    }

    public function test_call_reason_creation_logs_to_catalogos(): void
    {
        $reason = CallReason::create([
            "name" => "Test Reason",
            "color" => "#ff0000",
            "active" => true,
        ]);

        $activity = Activity::inLog("catalogos")->latest()->first();

        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($reason->id, $activity->subject_id);
        $this->assertEquals(CallReason::class, $activity->subject_type);
        $this->assertEquals("creó", $activity->description);
    }

    public function test_rate_creation_logs_to_catalogos(): void
    {
        $product = Product::create(["name" => "Parent", "value" => 1000]);
        $rate = Rate::create([
            "name" => "Test Rate",
            "product_id" => $product->id,
            "value" => 10000,
            "installments" => 12,
            "installment_value" => 1000,
            "discount" => 0,
            "active" => true,
        ]);

        $activities = Activity::inLog("catalogos")->orderBy("id")->get();
        $this->assertCount(
            2,
            $activities,
            "Expected 2 activities (product create + rate create), got: " .
                $activities->pluck("subject_type", "id")->toJson(),
        );

        $activity = $activities->last();
        $this->assertNotNull($activity);
        $this->assertEquals("catalogos", $activity->log_name);
        $this->assertEquals($rate->id, $activity->subject_id);
        $this->assertEquals(Rate::class, $activity->subject_type);
        $this->assertEquals("creó", $activity->description);
    }

    public function test_user_schedule_creation_logs_to_usuarios(): void
    {
        $user = User::factory()->create();
        $schedule = UserSchedule::create([
            "user_id" => $user->id,
            "day_of_week" => 1,
            "start_time" => "09:00",
            "end_time" => "18:00",
            "show_status" => true,
        ]);

        $activities = Activity::inLog("usuarios")->orderBy("id")->get();

        $this->assertCount(
            2,
            $activities,
            "Expected 2 activities (user create + schedule create), got: " .
                $activities->pluck("subject_type", "id")->toJson(),
        );

        $activity = $activities->last();
        $this->assertNotNull($activity);
        $this->assertEquals("usuarios", $activity->log_name);
        $this->assertEquals($schedule->id, $activity->subject_id);
        $this->assertEquals(UserSchedule::class, $activity->subject_type);
    }

    public function test_all_audited_models_have_log_name_configured(): void
    {
        $models = [
            new CallReason(),
            new Parameter(),
            new Permission(),
            new Product(),
            new Rate(),
            new Role(),
            new Seller(),
            new State(),
            new User(),
            new UserSchedule(),
        ];

        foreach ($models as $model) {
            $reflection = new \ReflectionMethod($model, "logName");
            $name = $reflection->invoke($model);
            $this->assertNotNull($name);
            $this->assertNotEmpty($name);
        }
    }

    public function test_all_audited_models_have_activity_fields_configured(): void
    {
        $models = [
            new CallReason(),
            new Parameter(),
            new Permission(),
            new Product(),
            new Rate(),
            new Role(),
            new Seller(),
            new State(),
            new User(),
            new UserSchedule(),
        ];

        foreach ($models as $model) {
            $reflection = new \ReflectionMethod($model, "activitylogFields");
            $fields = $reflection->invoke($model);
            $this->assertIsArray($fields);
            $this->assertNotEmpty($fields);
        }
    }
}
