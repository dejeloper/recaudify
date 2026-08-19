<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckMaintenanceMode;
use App\Services\ParameterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CheckMaintenanceModeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Si los parámetros no se pueden leer, el sistema queda abierto.
     *
     * Lo contrario sería dejar a todo el mundo afuera por un fallo de caché, sin que nadie lo haya
     * decidido y sin forma de entrar a apagarlo.
     */
    public function test_fails_open_when_parameters_cannot_be_read(): void
    {
        $parameters = Mockery::mock(ParameterService::class);
        $parameters->shouldReceive("get")->andThrow(new RuntimeException("base caída"));

        $middleware = new CheckMaintenanceMode($parameters);

        $response = $middleware->handle(Request::create("/api/activities", "GET"), fn() => new Response("ok", 200));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
