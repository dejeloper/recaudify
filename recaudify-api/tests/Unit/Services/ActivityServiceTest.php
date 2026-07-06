<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ActivityService::class);
    }

    private function makeActivity(array $attributes = []): Activity
    {
        return Activity::create(array_merge(
            [
                "log_name" => "default",
                "description" => "evento de prueba",
                "event" => "created",
            ],
            $attributes,
        ));
    }

    public function test_get_all_filters_by_causer_username(): void
    {
        $user = User::factory()->create(["username" => "jperez"]);
        $other = User::factory()->create(["username" => "otro"]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $other->id]);

        $result = $this->service->getAll(["user" => "jperez"]);

        $this->assertSame(1, $result->total());
        $this->assertSame($user->id, $result->items()[0]->causer_id);
    }

    public function test_get_all_filters_by_causer_username_unknown_returns_none(): void
    {
        $user = User::factory()->create(["username" => "jperez"]);
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);

        $result = $this->service->getAll(["user" => "no-existe"]);

        $this->assertSame(0, $result->total());
    }

    public function test_get_all_filters_by_sistema_user(): void
    {
        // Nota: crear un User dispara un log automático (log_name "usuarios") vía
        // LogsModelActivity, por eso las actividades manuales usan log_name "default"
        // para poder aislarlas del ruido generado por el factory.
        $user = User::factory()->create();
        $this->makeActivity(["causer_type" => User::class, "causer_id" => $user->id]);
        $this->makeActivity();

        $result = $this->service->getAll(["user" => "sistema", "log_name" => "default"]);

        $this->assertSame(1, $result->total());
        $this->assertNull($result->items()[0]->causer_id);
    }

    public function test_get_all_filters_by_log_name(): void
    {
        $this->makeActivity(["log_name" => "security"]);
        $this->makeActivity(["log_name" => "default"]);

        $result = $this->service->getAll(["log_name" => "security"]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_all_filters_by_model(): void
    {
        // Ver nota en test_get_all_filters_by_sistema_user sobre log_name "default".
        $user = User::factory()->create();
        $this->makeActivity(["subject_type" => User::class, "subject_id" => $user->id]);
        $this->makeActivity();

        $result = $this->service->getAll(["model" => "User", "log_name" => "default"]);

        $this->assertSame(1, $result->total());
    }

    public function test_get_all_attaches_subject_label_for_known_model(): void
    {
        $user = User::factory()->create(["name" => "Fabiola Guzmán"]);
        $this->makeActivity(["subject_type" => User::class, "subject_id" => $user->id]);

        $result = $this->service->getAll();

        $this->assertSame("Fabiola Guzmán", $result->items()[0]->subject_label);
    }

    public function test_get_all_skips_labels_for_unknown_subject_class(): void
    {
        $this->makeActivity(["subject_type" => "App\\Models\\NoExiste", "subject_id" => 1]);

        $result = $this->service->getAll();

        $this->assertNull($result->items()[0]->subject_label ?? null);
    }
}
