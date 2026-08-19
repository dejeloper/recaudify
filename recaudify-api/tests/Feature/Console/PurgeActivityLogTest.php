<?php

namespace Tests\Feature\Console;

use App\Enums\ParameterType;
use App\Models\Activity;
use App\Models\Parameter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class PurgeActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "activity_log_retention_days"],
            ["value" => "365", "cast" => "integer"],
        );
    }

    private function makeActivity(string $logName, string $createdAt): Activity
    {
        return Activity::create([
            "log_name" => $logName,
            "description" => "evento de prueba",
            "event" => "created",
            "created_at" => $createdAt,
        ]);
    }

    private function seedSystemUser(): User
    {
        Permission::firstOrCreate(["name" => "audit.purge", "guard_name" => "api"]);
        $role = Role::firstOrCreate(["name" => "sistema", "guard_name" => "api"]);
        $role->syncPermissions(["audit.purge"]);

        $user = User::factory()->create([
            "name" => "Sistema",
            "username" => User::SYSTEM_USERNAME,
            "active" => false,
        ]);
        $user->assignRole("sistema");

        return $user;
    }

    public function test_purges_expired_activity(): void
    {
        $this->makeActivity("viejo", now()->subDays(400)->toDateTimeString());
        $this->makeActivity("reciente", now()->subDays(10)->toDateTimeString());

        $this->artisan("activity:purge")->assertSuccessful();

        $this->assertEquals(0, Activity::where("log_name", "viejo")->count());
        $this->assertEquals(1, Activity::where("log_name", "reciente")->count());
    }

    public function test_accepts_explicit_days(): void
    {
        $this->makeActivity("test", now()->subDays(30)->toDateTimeString());

        $this->artisan("activity:purge --days=7")->assertSuccessful();

        $this->assertEquals(0, Activity::where("log_name", "test")->count());
    }

    public function test_rejects_invalid_days(): void
    {
        $this->artisan("activity:purge --days=0")->assertFailed();
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->makeActivity("viejo", now()->subDays(400)->toDateTimeString());

        $this->artisan("activity:purge --dry-run")->assertSuccessful();

        $this->assertEquals(1, Activity::where("log_name", "viejo")->count());
    }

    public function test_purge_is_signed_by_the_system_user(): void
    {
        $system = $this->seedSystemUser();
        $this->makeActivity("viejo", now()->subDays(400)->toDateTimeString());

        $this->artisan("activity:purge")->assertSuccessful();

        $audit = Activity::query()->where("log_name", "audit")->firstOrFail();

        $this->assertEquals($system->id, $audit->causer_id);
        $this->assertEquals(User::SYSTEM_USERNAME, $audit->causer_username);
        $this->assertEquals("Sistema", $audit->causer_name);
    }

    public function test_runs_without_a_system_user_but_warns(): void
    {
        $this->makeActivity("viejo", now()->subDays(400)->toDateTimeString());

        $this->artisan("activity:purge")->expectsOutputToContain("sistema")->assertSuccessful();

        $this->assertEquals(0, Activity::where("log_name", "viejo")->count());
    }

    public function test_purge_is_scheduled_daily(): void
    {
        $events = collect(Schedule::events())->filter(
            fn($event) => str_contains($event->command ?? "", "activity:purge"),
        );

        $this->assertCount(1, $events, "activity:purge debería estar programado una sola vez");
        $this->assertEquals("0 3 * * *", $events->first()->expression);
    }
}
