<?php

namespace Tests\Unit\Services;

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
        $this->service = new ParameterService();
        ParameterService::clearCache();
    }

    public function test_static_get_returns_value_or_default(): void
    {
        Parameter::create(["key" => "dias_mora", "value" => "45"]);
        ParameterService::clearCache();

        $this->assertSame("45", ParameterService::get("dias_mora"));
        $this->assertSame("fallback", ParameterService::get("inexistente", "fallback"));
    }

    public function test_create_invalidates_cache(): void
    {
        // Calienta la caché (vacía) antes de crear.
        $this->assertNull(ParameterService::get("dias_mora"));

        $this->service->create(["key" => "dias_mora", "value" => "45"]);

        // Tras crear, la caché se invalidó y refleja el nuevo valor.
        $this->assertSame("45", ParameterService::get("dias_mora"));
    }

    public function test_update_invalidates_cache(): void
    {
        $param = $this->service->create(["key" => "dias_mora", "value" => "45"]);
        $this->assertSame("45", ParameterService::get("dias_mora"));

        $this->service->update($param, ["value" => "60"]);

        $this->assertSame("60", ParameterService::get("dias_mora"));
    }

    public function test_delete_invalidates_cache(): void
    {
        $param = $this->service->create(["key" => "dias_mora", "value" => "45"]);
        $this->assertSame("45", ParameterService::get("dias_mora"));

        $this->service->delete($param);

        $this->assertSame("default", ParameterService::get("dias_mora", "default"));
    }
}
