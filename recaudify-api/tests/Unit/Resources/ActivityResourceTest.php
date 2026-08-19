<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityResourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeActivity(array $attributes = []): Activity
    {
        return Activity::create(
            array_merge(
                [
                    "log_name" => "default",
                    "description" => "evento de prueba",
                    "event" => "created",
                ],
                $attributes,
            ),
        );
    }

    public function test_resource_exposes_expected_shape(): void
    {
        $user = User::factory()->create(["name" => "Juan Pérez"]);
        $activity = $this->makeActivity([
            "log_name" => "usuarios",
            "event" => "updated",
            "causer_type" => User::class,
            "causer_id" => $user->id,
            "causer_username" => "jperez",
            "causer_name" => "Juan Pérez",
            "subject_type" => User::class,
            "subject_id" => 42,
            "properties" => ["attributes" => ["name" => "Nuevo"], "old" => ["name" => "Viejo"]],
        ]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame(
            ["id", "log_name", "event", "description", "model", "model_label", "subject", "causer", "changes", "created_at"],
            array_keys($data),
        );
    }

    public function test_build_causer_returns_null_when_no_causer(): void
    {
        $activity = $this->makeActivity(["causer_id" => null]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertNull($data["causer"]);
    }

    public function test_build_causer_returns_snapshot_when_causer_deleted(): void
    {
        $activity = $this->makeActivity([
            "causer_type" => User::class,
            "causer_id" => 9999,
            "causer_username" => "ghost",
            "causer_name" => "Ghost User",
        ]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame(9999, $data["causer"]["id"]);
        $this->assertSame("Ghost User", $data["causer"]["name"]);
        $this->assertSame("ghost", $data["causer"]["username"]);
        $this->assertFalse($data["causer"]["exists"]);
    }

    public function test_build_causer_prefers_snapshot_over_live_relation(): void
    {
        $user = User::factory()->create(["name" => "Nombre Nuevo", "username" => "nuevo"]);
        $activity = $this->makeActivity([
            "causer_type" => User::class,
            "causer_id" => $user->id,
            "causer_username" => "viejo",
            "causer_name" => "Nombre Viejo",
        ]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame("Nombre Viejo", $data["causer"]["name"]);
        $this->assertSame("viejo", $data["causer"]["username"]);
        $this->assertTrue($data["causer"]["exists"]);
    }

    public function test_build_causer_falls_back_to_relation_when_no_snapshot(): void
    {
        $user = User::factory()->create(["name" => "Directo", "username" => "directo"]);
        $activity = $this->makeActivity([
            "causer_type" => User::class,
            "causer_id" => $user->id,
            "causer_username" => null,
            "causer_name" => null,
        ]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame("Directo", $data["causer"]["name"]);
        $this->assertSame("directo", $data["causer"]["username"]);
        $this->assertTrue($data["causer"]["exists"]);
    }

    public function test_build_changes_returns_field_diffs(): void
    {
        $activity = $this->makeActivity([
            "properties" => [
                "attributes" => ["name" => "Nuevo", "email" => "nuevo@test.com"],
                "old" => ["name" => "Viejo", "email" => "viejo@test.com"],
            ],
        ]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertCount(2, $data["changes"]);
        $this->assertSame("name", $data["changes"][0]["field"]);
        $this->assertSame("Viejo", $data["changes"][0]["old"]);
        $this->assertSame("Nuevo", $data["changes"][0]["new"]);
    }

    public function test_build_changes_empty_when_no_changes(): void
    {
        $activity = $this->makeActivity(["properties" => []]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame([], $data["changes"]);
    }

    public function test_model_label_resolves_known_models(): void
    {
        $activity = $this->makeActivity(["subject_type" => User::class, "subject_id" => 1]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame("User", $data["model"]);
        $this->assertSame("usuario", $data["model_label"]);
    }

    public function test_model_label_lowercases_unknown_models(): void
    {
        $activity = $this->makeActivity(["subject_type" => "App\\Models\\CustomThing", "subject_id" => 1]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertSame("CustomThing", $data["model"]);
        $this->assertSame("customthing", $data["model_label"]);
    }

    public function test_model_and_label_null_when_no_subject(): void
    {
        $activity = $this->makeActivity(["subject_type" => null, "subject_id" => null]);

        $data = (new ActivityResource($activity))->toArray(Request::create("/"));

        $this->assertNull($data["model"]);
        $this->assertNull($data["model_label"]);
    }
}
