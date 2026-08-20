<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BranchServiceTest extends TestCase
{
    use RefreshDatabase;

    private BranchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(BranchService::class);
    }

    private function makeBranch(array $overrides = []): Branch
    {
        return Branch::create(array_merge([
            "code" => "MATRIZ",
            "name" => "Sucursal Principal",
            "email" => "matriz@test.com",
            "is_main" => true,
        ], $overrides));
    }

    public function test_create_first_branch_becomes_main(): void
    {
        $branch = $this->service->create(["code" => "SUC01", "name" => "Sede 1", "email" => "s1@test.com"]);

        $this->assertTrue($branch->is_main);
    }

    public function test_create_promotes_first_when_no_main_exists(): void
    {
        $branch = $this->service->create(["code" => "SUC01", "name" => "Sede 1", "email" => "s1@test.com", "is_main" => false]);

        $this->assertTrue($branch->is_main);
    }

    public function test_create_does_not_promote_when_main_exists(): void
    {
        $this->makeBranch(["is_main" => true]);
        $branch = $this->service->create(["code" => "SUC02", "name" => "Sede 2", "email" => "s2@test.com", "is_main" => false]);

        $this->assertFalse($branch->is_main);
    }

    public function test_create_demotes_other_mains_when_marked_as_main(): void
    {
        $oldMain = $this->makeBranch(["code" => "OLD", "name" => "Vieja Principal", "email" => "old@test.com", "is_main" => true]);
        $newMain = $this->service->create(["code" => "NEW", "name" => "Nueva Principal", "email" => "new@test.com", "is_main" => true]);

        $this->assertTrue($newMain->fresh()->is_main);
        $this->assertFalse($oldMain->fresh()->is_main);
    }

    public function test_update_demotes_others_when_promoting(): void
    {
        $oldMain = $this->makeBranch(["code" => "OLD", "name" => "Vieja Principal", "email" => "old@test.com", "is_main" => true]);
        $other = $this->makeBranch(["code" => "SUC01", "name" => "Sede 1", "email" => "s1@test.com", "is_main" => false]);

        $this->service->update($other, ["is_main" => true]);

        $this->assertTrue($other->fresh()->is_main);
        $this->assertFalse($oldMain->fresh()->is_main);
    }

    public function test_update_throws_when_demoting_only_main(): void
    {
        $main = $this->makeBranch(["code" => "MATRIZ", "name" => "Principal", "email" => "m@test.com", "is_main" => true]);

        $this->expectException(ValidationException::class);
        $this->service->update($main, ["is_main" => false]);
    }

    public function test_update_allows_same_name_for_self(): void
    {
        $branch = $this->makeBranch(["name" => "Original"]);

        $this->service->update($branch, ["name" => "Original"]);

        $this->assertSame("Original", $branch->fresh()->name);
    }

    public function test_delete_throws_when_main(): void
    {
        $main = $this->makeBranch();

        $this->expectException(ValidationException::class);
        $this->service->delete($main);
    }

    public function test_delete_throws_when_has_users(): void
    {
        $branch = $this->makeBranch(["code" => "SUC01", "name" => "Con Usuarios", "email" => "s@test.com", "is_main" => false]);
        User::factory()->create(["branch_id" => $branch->id]);

        $this->expectException(ValidationException::class);
        $this->service->delete($branch);
    }

    public function test_delete_succeeds_when_not_main_and_no_users(): void
    {
        $branch = $this->makeBranch(["code" => "SUC01", "name" => "Vacía", "email" => "v@test.com", "is_main" => false]);

        $this->service->delete($branch);

        $this->assertSoftDeleted("branches", ["id" => $branch->id]);
    }

    public function test_restore_returns_branch(): void
    {
        $branch = $this->makeBranch(["code" => "SUC01", "name" => "Eliminada", "email" => "e@test.com", "is_main" => false]);
        $branch->delete();

        $restored = $this->service->restore($branch);

        $this->assertNull($restored->deleted_at);
    }

    public function test_find_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->find(999));
    }

    public function test_main_returns_null_when_none_exists(): void
    {
        $this->assertNull($this->service->main());
    }

    public function test_all_returns_ordered_collection(): void
    {
        $this->makeBranch(["code" => "B", "name" => "Zeta", "email" => "z@test.com", "is_main" => false]);
        $this->makeBranch(["code" => "A", "name" => "Alpha", "email" => "a@test.com", "is_main" => false]);

        $names = $this->service->all()->pluck("name")->values()->all();

        $this->assertEquals("Alpha", $names[0]);
        $this->assertEquals("Zeta", $names[1]);
    }
}
