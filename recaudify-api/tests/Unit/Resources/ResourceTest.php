<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ParameterResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserScheduleResource;
use App\Models\Parameter;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_resource_exposes_expected_shape(): void
    {
        $role = Role::create(["name" => "cobrador", "guard_name" => "api"]);
        Permission::create(["name" => "clientes.ver", "guard_name" => "api"]);
        $role->givePermissionTo("clientes.ver");

        $user = User::factory()->create(["name" => "Juan", "username" => "juan", "email" => null]);
        $user->assignRole("cobrador");

        $data = (new UserResource($user))->toArray(Request::create("/"));

        $this->assertSame(
            ["id", "name", "username", "email", "active", "branch_id", "branch", "roles", "permissions"],
            array_keys($data),
        );
        $this->assertSame("juan", $data["username"]);
        $this->assertContains("cobrador", $data["roles"]->all());
        $this->assertContains("clientes.ver", $data["permissions"]->all());
    }

    public function test_parameter_resource_exposes_expected_shape(): void
    {
        $param = Parameter::create([
            "type" => "authentication",
            "key" => "max_intentos",
            "value" => "5",
            "cast" => "integer",
            "description" => "Intentos máximos",
        ]);

        $data = (new ParameterResource($param))->toArray(Request::create("/"));

        $this->assertSame(
            [
                "id",
                "type",
                "type_label",
                "key",
                "value",
                "typed_value",
                "cast",
                "description",
                "is_editable",
                "updated_at",
            ],
            array_keys($data),
        );
        $this->assertSame("max_intentos", $data["key"]);
        $this->assertSame("5", $data["value"]);
        $this->assertSame("authentication", $data["type"]);
        $this->assertSame(5, $data["typed_value"]);
        $this->assertSame("integer", $data["cast"]);
    }

    public function test_user_schedule_resource_formats_day_and_time(): void
    {
        $user = User::factory()->create();
        $schedule = UserSchedule::create([
            "user_id" => $user->id,
            "day_of_week" => 1,
            "start_time" => "08:00",
            "end_time" => "17:00",
            "show_status" => true,
        ]);

        $data = (new UserScheduleResource($schedule))->toArray(Request::create("/"));

        $this->assertSame("Lunes", $data["day_name"]);
        $this->assertSame("08:00", $data["start_time"]);
        $this->assertSame("17:00", $data["end_time"]);
        $this->assertTrue($data["show_status"]);
    }
}
