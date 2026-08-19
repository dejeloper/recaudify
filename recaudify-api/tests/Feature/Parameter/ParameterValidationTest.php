<?php

namespace Tests\Feature\Parameter;

use App\Enums\ParameterType;
use App\Models\Parameter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterValidationTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = ["parameters.view", "parameters.create", "parameters.edit"];

    private function makeParameter(string $key, string $value, string $cast, string $type = "configuration"): Parameter
    {
        return Parameter::create([
            "type" => $type,
            "key" => $key,
            "value" => $value,
            "cast" => $cast,
            "is_editable" => true,
        ]);
    }

    private function update(Parameter $parameter, string $value)
    {
        return $this->putJson("/api/parameters/{$parameter->id}", ["value" => $value]);
    }

    // ── Validación por cast ────────────────────────────────────────

    public function test_integer_parameter_rejects_text(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_number", "10", "integer");

        $this->update($parameter, "diez")->assertStatus(422);
        $this->assertEquals("10", $parameter->fresh()->value);
    }

    public function test_integer_parameter_rejects_decimals(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_number", "10", "integer");

        $this->update($parameter, "10.5")->assertStatus(422);
    }

    public function test_integer_parameter_accepts_integers(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_number", "10", "integer");

        $this->update($parameter, "42")->assertStatus(200);
        $this->assertEquals("42", $parameter->fresh()->value);
    }

    public function test_boolean_parameter_rejects_anything_else(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_flag", "true", "boolean");

        $this->update($parameter, "quizás")->assertStatus(422);
    }

    public function test_boolean_parameter_accepts_true_and_false(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_flag", "true", "boolean");

        $this->update($parameter, "false")->assertStatus(200);
        $this->update($parameter, "1")->assertStatus(200);
    }

    public function test_json_parameter_rejects_broken_json(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("some_json", '["a"]', "json");

        $this->update($parameter, "{roto")->assertStatus(422);
        $this->update($parameter, '["a","b"]')->assertStatus(200);
    }

    // ── Validación por clave ───────────────────────────────────────

    public function test_maintenance_scope_only_accepts_known_values(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("maintenance_scope", "all", "string");

        $response = $this->update($parameter, "todo")->assertStatus(422);
        $response->assertJsonPath("data.value.0", "Debe ser 'all' (bloquea todo) o 'writes' (bloquea solo guardar).");

        $this->update($parameter, "writes")->assertStatus(200);
    }

    public function test_purge_time_requires_hh_mm(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("activity_log_purge_time", "03:00", "string", "application");

        $this->update($parameter, "25:99")->assertStatus(422);
        $this->update($parameter, "3:00")->assertStatus(422);
        $this->update($parameter, "23:59")->assertStatus(200);
    }

    public function test_login_field_only_accepts_username_or_email(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("login_field", "username", "string", "authentication");

        $this->update($parameter, "documento")->assertStatus(422);
        $this->update($parameter, "email")->assertStatus(200);
    }

    public function test_numeric_parameters_respect_their_range(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("max_login_attempts", "5", "integer", "authentication");

        $this->update($parameter, "0")->assertStatus(422);
        $this->update($parameter, "999")->assertStatus(422);
        $this->update($parameter, "10")->assertStatus(200);
    }

    public function test_timezone_must_be_real(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("timezone", "America/Bogota", "string", "application");

        $this->update($parameter, "America/Narnia")->assertStatus(422);
        $this->update($parameter, "America/Bogota")->assertStatus(200);
    }

    public function test_currency_must_be_a_three_letter_code(): void
    {
        $this->authenticateWith(self::PERMISSIONS);
        $parameter = $this->makeParameter("currency", "COP", "string", "application");

        $this->update($parameter, "pesos")->assertStatus(422);
        $this->update($parameter, "USD")->assertStatus(200);
    }

    // ── Al crear ───────────────────────────────────────────────────

    public function test_rules_also_apply_when_creating(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/parameters", [
            "type" => ParameterType::Configuration->value,
            "key" => "maintenance_scope",
            "value" => "cualquier_cosa",
            "cast" => "string",
        ])->assertStatus(422);
    }

    public function test_unknown_keys_are_still_validated_by_cast(): void
    {
        $this->authenticateWith(self::PERMISSIONS);

        $this->postJson("/api/parameters", [
            "type" => ParameterType::Configuration->value,
            "key" => "parametro_inventado",
            "value" => "no soy un numero",
            "cast" => "integer",
        ])->assertStatus(422);

        $this->postJson("/api/parameters", [
            "type" => ParameterType::Configuration->value,
            "key" => "parametro_inventado",
            "value" => "123",
            "cast" => "integer",
        ])->assertStatus(201);
    }
}
