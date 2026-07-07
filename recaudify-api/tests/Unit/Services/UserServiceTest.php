<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(UserService::class);
        Role::firstOrCreate(["name" => "cobrador", "guard_name" => "api"]);
        Permission::firstOrCreate(["name" => "clientes.ver", "guard_name" => "api"]);
    }

    public function test_create_assigns_role(): void
    {
        $user = $this->service->create(
            ["name" => "Juan", "username" => "juan", "password" => Hash::make("secret1234")],
            "cobrador",
        );

        $this->assertTrue($user->hasRole("cobrador"));
    }

    public function test_update_keeps_password_when_empty(): void
    {
        $user = User::factory()->create(["password" => Hash::make("original1234")]);
        $original = $user->password;

        $this->service->update($user, ["name" => "Nuevo", "password" => ""]);

        $this->assertSame($original, $user->fresh()->password); // no se sobreescribe con vacío
        $this->assertSame("Nuevo", $user->fresh()->name);
    }

    public function test_update_changes_password_when_provided(): void
    {
        $user = User::factory()->create(["password" => Hash::make("original1234")]);
        $original = $user->password;

        $this->service->update($user, ["password" => Hash::make("nuevo12345")]);

        $this->assertNotSame($original, $user->fresh()->password);
    }

    public function test_search_matches_name_or_username(): void
    {
        User::factory()->create(["name" => "Fabiola Guzmán", "username" => "fabi"]);
        User::factory()->create(["name" => "Otro", "username" => "guzman99"]);

        $this->assertCount(1, $this->service->search("Fabiola"));
        $this->assertCount(1, $this->service->search("guzman99"));
    }

    public function test_sync_permissions_assigns_and_revokes(): void
    {
        $user = User::factory()->create();

        $assigned = $this->service->syncPermissions($user, ["clientes.ver"], true);
        $this->assertContains("clientes.ver", $assigned->all());

        $revoked = $this->service->syncPermissions($user, ["clientes.ver"], false);
        $this->assertNotContains("clientes.ver", $revoked->all());
    }

    public function test_delete_and_restore_user(): void
    {
        $user = User::factory()->create();

        $this->service->delete($user);
        $this->assertCount(1, $this->service->allDisabled());

        $this->service->restore($this->service->findTrashed($user->id));
        $this->assertCount(0, $this->service->allDisabled());
    }
}
