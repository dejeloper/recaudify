<?php

namespace Tests\Feature\Parameter;

use App\Models\Parameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        "parameters.view",
        "parameters.create",
        "parameters.edit",
        "parameters.delete",
        "parameters.restore",
    ];

    private const PAYLOAD = [
        "type" => "configuration",
        "key" => "dias_mora",
        "value" => "45",
        "cast" => "integer",
        "description" => "Días de mora",
    ];

    public function test_index_lists_parameters(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Parameter::create(self::PAYLOAD);

        $this->getJson("/api/parameters")->assertStatus(200)->assertJsonPath("success", true);
    }

    public function test_store_creates_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/parameters", self::PAYLOAD)->assertStatus(201);
        $this->assertDatabaseHas("parameters", ["key" => "dias_mora", "value" => "45"]);
    }

    public function test_store_validates_required(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/parameters", [])->assertStatus(422);
    }

    public function test_store_validates_unique(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        Parameter::create(self::PAYLOAD);

        $this->postJson("/api/parameters", self::PAYLOAD)->assertStatus(422);
    }

    public function test_update_modifies_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $param = Parameter::create(self::PAYLOAD);

        $this->putJson("/api/parameters/{$param->id}", ["value" => "60"])->assertStatus(200);
        $this->assertDatabaseHas("parameters", ["id" => $param->id, "value" => "60"]);
    }

    public function test_destroy_soft_deletes_parameter(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $param = Parameter::create(self::PAYLOAD);

        $this->deleteJson("/api/parameters/{$param->id}")->assertStatus(200);
        $this->assertSoftDeleted("parameters", ["id" => $param->id]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/api/parameters")->assertStatus(401);
    }
}
