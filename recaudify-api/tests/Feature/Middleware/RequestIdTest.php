<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\AssignRequestId;
use App\Services\LoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequestIdTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function test_every_response_carries_a_request_id(): void
    {
        $response = $this->getJson("/api/health")->assertStatus(200);

        $id = $response->headers->get(AssignRequestId::HEADER);

        $this->assertNotNull($id);
        $this->assertMatchesRegularExpression(self::UUID, $id);
    }

    public function test_each_request_gets_its_own_id(): void
    {
        $first = $this->getJson("/api/health")->headers->get(AssignRequestId::HEADER);
        $second = $this->getJson("/api/health")->headers->get(AssignRequestId::HEADER);

        $this->assertNotEquals($first, $second);
    }

    public function test_respects_an_id_sent_by_the_client(): void
    {
        $response = $this->getJson("/api/health", [AssignRequestId::HEADER => "front-abc-123"]);

        $this->assertEquals("front-abc-123", $response->headers->get(AssignRequestId::HEADER));
    }

    /**
     * El id termina escrito en los archivos de log: aceptar cualquier cosa permitiría inyectar
     * líneas falsas con un salto de línea.
     */
    public function test_rejects_a_malformed_id_and_generates_its_own(): void
    {
        foreach (["corto", "con espacios", "salto\nde-linea", str_repeat("x", 100), "punto;coma"] as $invalid) {
            $response = $this->getJson("/api/health", [AssignRequestId::HEADER => $invalid]);

            $id = $response->headers->get(AssignRequestId::HEADER);

            $this->assertNotEquals($invalid, $id, "El id inválido '{$invalid}' no debería aceptarse");
            $this->assertMatchesRegularExpression(self::UUID, $id);
        }
    }

    public function test_error_responses_also_carry_the_id(): void
    {
        Route::middleware("api")->get("/api/test-boom", fn() => throw new \RuntimeException("boom"));

        $response = $this->getJson("/api/test-boom")->assertStatus(500);

        $this->assertNotNull($response->headers->get(AssignRequestId::HEADER));
    }

    public function test_unauthenticated_responses_also_carry_the_id(): void
    {
        $response = $this->getJson("/api/activities")->assertStatus(401);

        $this->assertNotNull($response->headers->get(AssignRequestId::HEADER));
    }

    /** El id tiene que llegar a los cuatro canales: si falta en uno, ese queda fuera de la traza. */
    public function test_request_id_reaches_every_log_channel(): void
    {
        Context::add(AssignRequestId::CONTEXT_KEY, "req-de-prueba-1234");

        $stamped = fn(array $context) => ($context[AssignRequestId::CONTEXT_KEY] ?? null) === "req-de-prueba-1234";

        $spy = Log::spy();
        $spy->shouldReceive("channel")->andReturnSelf();

        $logging = app(LoggingService::class);
        $logging->logBusiness("evento de negocio", ["extra" => 1]);
        $logging->logRequest(["path" => "api/test"]);
        $logging->logSecurity("acceso denegado");
        $logging->logError(new \RuntimeException("boom"));

        $spy->shouldHaveReceived("info")->twice()->withArgs(fn(string $message, array $context) => $stamped($context));

        $spy->shouldHaveReceived("warning")
            ->once()
            ->withArgs(fn(string $message, array $context) => $stamped($context));

        $spy->shouldHaveReceived("error")
            ->once()
            ->withArgs(fn(string $message, array $context) => $stamped($context) && $context["exception"] !== null);
    }

    public function test_business_context_keeps_its_own_data(): void
    {
        Context::add(AssignRequestId::CONTEXT_KEY, "req-de-prueba-1234");

        $spy = Log::spy();
        $spy->shouldReceive("channel")->andReturnSelf();

        app(LoggingService::class)->logBusiness("evento de negocio", ["extra" => 1]);

        $spy->shouldHaveReceived("info")->withArgs(
            fn(string $message, array $context) => $message === "evento de negocio" && $context["extra"] === 1,
        );
    }
}
