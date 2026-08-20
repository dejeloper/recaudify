<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        "branches.view",
        "branches.create",
        "branches.edit",
        "branches.delete",
        "branches.restore",
        "branches.view-all",
    ];

    private function makeBranch(array $attributes = []): Branch
    {
        return Branch::create(
            array_merge(
                [
                    "code" => "PRINCIPAL",
                    "name" => "Sede Principal",
                    "is_main" => false,
                    "sort_order" => 0,
                ],
                $attributes,
            ),
        );
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/branches")->assertStatus(401);
    }

    public function test_index_lists_branches(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch();
        $this->makeBranch(["code" => "MED-POB", "name" => "Medellín - Poblado"]);

        $this->getJson("/api/branches")->assertStatus(200)->assertJsonCount(2, "data");
    }

    public function test_index_filters_by_search(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch();
        $this->makeBranch(["code" => "MED-POB", "name" => "Medellín - Poblado", "city" => "Medellín"]);

        $response = $this->getJson("/api/branches?search=MED")->assertStatus(200);

        $response->assertJsonCount(1, "data");
        $response->assertJsonPath("data.0.code", "MED-POB");
    }

    public function test_store_creates_branch_and_first_one_becomes_main(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/branches", [
            "code" => "bog-cen",
            "name" => "Bogotá - Centro",
            "city" => "Bogotá",
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.code", "BOG-CEN");
        $response->assertJsonPath("data.is_main", true);
        $this->assertDatabaseHas("branches", ["code" => "BOG-CEN", "is_main" => true]);
    }

    public function test_store_does_not_promote_second_branch_to_main(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch(["is_main" => true]);

        $response = $this->postJson("/api/branches", ["code" => "MED-POB", "name" => "Medellín - Poblado"]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.is_main", false);
    }

    public function test_store_rejects_duplicated_code(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch(["code" => "BOG-CEN", "name" => "Bogotá - Centro"]);

        $this->postJson("/api/branches", ["code" => "BOG-CEN", "name" => "Otra sede"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["code"]]);
    }

    public function test_store_rejects_invalid_code_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/branches", ["code" => "bog cen!", "name" => "Bogotá - Centro"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["code"]]);
    }

    public function test_marking_a_branch_as_main_demotes_the_previous_one(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $main = $this->makeBranch(["is_main" => true]);
        $other = $this->makeBranch(["code" => "MED-POB", "name" => "Medellín - Poblado"]);

        $this->putJson("/api/branches/{$other->id}", ["is_main" => true])->assertStatus(200);

        $this->assertDatabaseHas("branches", ["id" => $other->id, "is_main" => true]);
        $this->assertDatabaseHas("branches", ["id" => $main->id, "is_main" => false]);
    }

    public function test_cannot_leave_the_system_without_a_main_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $main = $this->makeBranch(["is_main" => true]);

        $this->putJson("/api/branches/{$main->id}", ["is_main" => false])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["is_main"]]);
    }

    public function test_cannot_delete_main_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $main = $this->makeBranch(["is_main" => true]);

        $this->deleteJson("/api/branches/{$main->id}")
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["branch"]]);
    }

    public function test_cannot_delete_branch_with_users(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();
        User::factory()->create(["branch_id" => $branch->id]);

        $this->deleteJson("/api/branches/{$branch->id}")
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["branch"]]);
    }

    public function test_delete_and_restore_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();

        $this->deleteJson("/api/branches/{$branch->id}")->assertStatus(200);
        $this->assertSoftDeleted("branches", ["id" => $branch->id]);

        $this->getJson("/api/branches/trashed")->assertStatus(200)->assertJsonCount(1, "data");

        $this->postJson("/api/branches/{$branch->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("branches", ["id" => $branch->id, "deleted_at" => null]);
    }

    public function test_main_endpoint_returns_main_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch(["is_main" => true]);

        $this->getJson("/api/branches/main")->assertStatus(200)->assertJsonPath("data.is_main", true);
    }

    public function test_main_endpoint_returns_404_without_main_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/branches/main")->assertStatus(404);
    }

    public function test_show_returns_404_for_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->getJson("/api/branches/999")->assertStatus(404);
    }

    public function test_deleted_code_can_be_reused(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch(["code" => "BOG-CEN", "name" => "Bogotá - Centro"]);
        $branch->delete();

        $this->postJson("/api/branches", ["code" => "BOG-CEN", "name" => "Bogotá - Centro"])->assertStatus(201);
    }

    public function test_show_returns_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch(["code" => "MED-POB", "name" => "Medellín - Poblado", "city" => "Medellín"]);

        $response = $this->getJson("/api/branches/{$branch->id}")->assertStatus(200);

        $response->assertJsonPath("data.code", "MED-POB");
        $response->assertJsonPath("data.name", "Medellín - Poblado");
        $response->assertJsonPath("data.city", "Medellín");
    }

    public function test_store_validates_required_fields(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/branches", [])->assertStatus(422)
            ->assertJsonStructure(["data" => ["code", "name"]]);
    }

    public function test_store_validates_email_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/branches", [
            "code" => "BOG-CEN",
            "name" => "Bogotá - Centro",
            "email" => "no-es-email",
        ])->assertStatus(422)->assertJsonStructure(["data" => ["email"]]);
    }

    public function test_store_code_is_uppercased_automatically(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $response = $this->postJson("/api/branches", [
            "code" => "med-pob",
            "name" => "Medellín - Poblado",
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath("data.code", "MED-POB");
    }

    public function test_update_modifies_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();

        $this->putJson("/api/branches/{$branch->id}", [
            "name" => "Sede Renombrada",
            "city" => "Cali",
        ])->assertStatus(200);

        $this->assertDatabaseHas("branches", ["id" => $branch->id, "name" => "Sede Renombrada", "city" => "Cali"]);
    }

    public function test_update_rejects_duplicated_code(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch(["code" => "BOG-CEN", "name" => "Bogotá - Centro"]);
        $other = $this->makeBranch(["code" => "MED-POB", "name" => "Medellín - Poblado"]);

        $this->putJson("/api/branches/{$other->id}", ["code" => "BOG-CEN"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["code"]]);
    }

    public function test_update_rejects_invalid_code_format(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();

        $this->putJson("/api/branches/{$branch->id}", ["code" => "invalid code!"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["code"]]);
    }

    public function test_update_returns_404_for_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->putJson("/api/branches/999", ["name" => "Test"])->assertStatus(404);
    }

    public function test_delete_returns_404_for_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->deleteJson("/api/branches/999")->assertStatus(404);
    }

    public function test_restore_returns_404_for_unknown_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/branches/999/restore")->assertStatus(404);
    }

    public function test_restore_returns_404_for_non_trashed_branch(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();

        $this->postJson("/api/branches/{$branch->id}/restore")->assertStatus(404);
    }

    public function test_trashed_lists_deleted_branches(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch(["code" => "BOG-CEN", "name" => "Bogotá"]);
        $branch->delete();
        $this->makeBranch(["code" => "MED-POB", "name" => "Medellín"]);

        $this->getJson("/api/branches/trashed")->assertStatus(200)->assertJsonCount(1, "data");
    }

    public function test_index_excludes_deleted_branches(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $branch = $this->makeBranch();
        $branch->delete();
        $this->makeBranch(["code" => "MED-POB", "name" => "Medellín"]);

        $this->getJson("/api/branches")->assertStatus(200)->assertJsonCount(1, "data");
    }

    public function test_store_rejects_duplicated_name(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $this->makeBranch(["code" => "BOG-CEN", "name" => "Bogotá - Centro"]);

        $this->postJson("/api/branches", ["code" => "OTRO", "name" => "Bogotá - Centro"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["name"]]);
    }

    public function test_store_validates_sort_order_is_non_negative(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/branches", [
            "code" => "BOG-CEN",
            "name" => "Bogotá",
            "sort_order" => -1,
        ])->assertStatus(422)->assertJsonStructure(["data" => ["sort_order"]]);
    }
}
