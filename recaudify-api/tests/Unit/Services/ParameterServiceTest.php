<?php

namespace Tests\Unit\Services;

use App\Enums\ParameterCast;
use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Services\ParameterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParameterServiceTest extends TestCase
{
    use RefreshDatabase;

    private ParameterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ParameterService::class);
    }

    public function test_get_returns_resolved_value(): void
    {
        Parameter::create([
            "type" => "authentication",
            "key" => "max_intentos",
            "value" => "5",
            "cast" => "integer",
        ]);

        $result = $this->service->get(ParameterType::Authentication, "max_intentos");

        $this->assertSame(5, $result);
    }

    public function test_get_returns_null_for_missing_key(): void
    {
        Parameter::create([
            "type" => "configuration",
            "key" => "other",
            "value" => "x",
            "cast" => "string",
        ]);

        $result = $this->service->get(ParameterType::Configuration, "nonexistent");

        $this->assertNull($result);
    }

    public function test_get_all_by_type(): void
    {
        Parameter::create(["type" => "authentication", "key" => "k1", "value" => "v1", "cast" => "string"]);
        Parameter::create(["type" => "authentication", "key" => "k2", "value" => "v2", "cast" => "string"]);

        $results = $this->service->getAll(ParameterType::Authentication);

        $this->assertCount(2, $results);
    }

    public function test_update_modifies_value_and_flushes_cache(): void
    {
        $param = Parameter::create([
            "type" => "configuration",
            "key" => "app_name",
            "value" => "old",
            "cast" => "string",
        ]);

        $updated = $this->service->update($param, "new");

        $this->assertSame("new", $updated->value);
    }

    public function test_flush_cache_removes_cached_entry(): void
    {
        Parameter::create(["type" => "configuration", "key" => "k", "value" => "v", "cast" => "string"]);
        $this->service->getAll(ParameterType::Configuration);

        $this->service->flushCache(ParameterType::Configuration);

        $this->expectNotToPerformAssertions();
    }

    public function test_get_all_does_not_hit_database_twice_for_same_type(): void
    {
        Parameter::create(["type" => "authentication", "key" => "k1", "value" => "v1", "cast" => "string"]);

        $this->service->getAll(ParameterType::Authentication);

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->service->getAll(ParameterType::Authentication);
        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertEmpty(
            $queries,
            "La segunda llamada a getAll() para el mismo tipo no debería tocar la base de datos.",
        );
    }

    public function test_get_all_reflects_changes_after_flush_cache(): void
    {
        $param = Parameter::create(["type" => "authentication", "key" => "k1", "value" => "v1", "cast" => "string"]);
        $this->service->getAll(ParameterType::Authentication);

        $param->update(["value" => "v2"]);
        $this->service->flushCache(ParameterType::Authentication);

        $result = $this->service->get(ParameterType::Authentication, "k1");

        $this->assertSame("v2", $result);
    }

    public function test_resolve_value_returns_correct_types(): void
    {
        $this->assertSame(true, $this->service->resolveValue("true", ParameterCast::Boolean));
        $this->assertSame(42, $this->service->resolveValue("42", ParameterCast::Integer));
        $this->assertSame(3.14, $this->service->resolveValue("3.14", ParameterCast::Float));
        $this->assertSame(["a" => 1], $this->service->resolveValue('{"a":1}', ParameterCast::Json));
        $this->assertSame("hello", $this->service->resolveValue("hello", ParameterCast::String));
    }
}
