<?php

namespace Tests\Feature\Parameter;

use App\Models\Parameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        "parametros.ver",
        "parametros.crear",
        "parametros.editar",
        "parametros.eliminar",
        "parametros.restaurar",
    ];

    public function test_index_lists_parameters(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Parameter::create(["key" => "max_intentos", "value" => "5"]);

        $this->getJson("/api/parameters")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_show_returns_parameter_or_404(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $param = Parameter::create(["key" => "max_intentos", "value" => "5"]);

        $this->getJson("/api/parameters/{$param->id}")
            ->assertStatus(200)
            ->assertJsonPath("data.key", "max_intentos");
        $this->getJson("/api/parameters/99999")->assertStatus(404);
    }

    public function test_store_creates_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/parameters", ["key" => "dias_mora", "value" => "45"])->assertStatus(201);
        $this->assertDatabaseHas("parameters", ["key" => "dias_mora", "value" => "45"]);
    }

    public function test_store_validates_required_and_unique(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Parameter::create(["key" => "dias_mora", "value" => "45"]);

        $this->postJson("/api/parameters", [])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["key", "value"]]);
        $this->postJson("/api/parameters", ["key" => "dias_mora", "value" => "90"])
            ->assertStatus(422)
            ->assertJsonStructure(["data" => ["key"]]);
    }

    public function test_update_modifies_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $param = Parameter::create(["key" => "dias_mora", "value" => "45"]);

        $this->putJson("/api/parameters/{$param->id}", ["key" => "dias_mora", "value" => "60"])->assertStatus(200);
        $this->assertDatabaseHas("parameters", ["id" => $param->id, "value" => "60"]);
    }

    public function test_destroy_and_restore_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $param = Parameter::create(["key" => "dias_mora", "value" => "45"]);

        $this->deleteJson("/api/parameters/{$param->id}")->assertStatus(200);
        $this->assertSoftDeleted("parameters", ["id" => $param->id]);

        $this->getJson("/api/parameters/trashed")->assertStatus(200);
        $this->postJson("/api/parameters/{$param->id}/restore")->assertStatus(200);
        $this->assertDatabaseHas("parameters", ["id" => $param->id, "deleted_at" => null]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/parameters")->assertStatus(401);
    }
}
