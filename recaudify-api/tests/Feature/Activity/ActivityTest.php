<?php

namespace Tests\Feature\Activity;

use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["audit.view"];

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "pagination_per_page"],
            ["value" => "25", "cast" => "integer"],
        );
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "pagination_max_per_page"],
            ["value" => "100", "cast" => "integer"],
        );
    }

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

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/activities")->assertStatus(401);
    }

    public function test_index_lists_activities_with_expected_shape(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $causer = User::factory()->create(["name" => "Fabiola Guzmán"]);
        $subject = User::factory()->create(["name" => "Cliente Uno"]);

        $this->makeActivity([
            "log_name" => "test",
            "causer_type" => User::class,
            "causer_id" => $causer->id,
            "subject_type" => User::class,
            "subject_id" => $subject->id,
            "attribute_changes" => ["attributes" => ["name" => "Nuevo"], "old" => ["name" => "Viejo"]],
        ]);

        $response = $this->getJson("/api/activities?log_name=test")->assertStatus(200);

        $response->assertJsonPath("success", true);
        $response->assertJsonPath("data.items.0.model", "User");
        $response->assertJsonPath("data.items.0.subject.label", "Cliente Uno");
        $response->assertJsonPath("data.items.0.causer.name", "Fabiola Guzmán");
        $response->assertJsonPath("data.items.0.changes.0.field", "name");
        $response->assertJsonPath("data.items.0.changes.0.old", "Viejo");
        $response->assertJsonPath("data.items.0.changes.0.new", "Nuevo");
    }

    public function test_index_filters_by_user(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $user = User::factory()->create(["username" => "jperez"]);
        $this->makeActivity(["log_name" => "test", "causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity(["log_name" => "test"]);

        $response = $this->getJson("/api/activities?log_name=test&user=jperez")->assertStatus(200);

        $response->assertJsonCount(1, "data.items");
    }

    public function test_index_paginates_results(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        for ($i = 0; $i < 3; $i++) {
            $this->makeActivity(["log_name" => "test"]);
        }

        $response = $this->getJson("/api/activities?log_name=test&per_page=2")->assertStatus(200);

        $response->assertJsonCount(2, "data.items");
        $response->assertJsonPath("data.meta.total", 3);
    }
}
