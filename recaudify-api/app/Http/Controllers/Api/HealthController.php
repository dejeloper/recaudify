<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResult;
use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends ApiController
{
    public function __construct(private readonly HealthService $healthService) {}

    /**
     * Estado de la aplicación y sus dependencias.
     *
     * Público a propósito: un chequeo de uptime tiene que poder llamarlo sin credenciales, y si
     * exigiera token no serviría justo cuando la base está caída. Por eso no devuelve mensajes de
     * error ni versiones: solo qué componente responde y cuál no.
     */
    public function index(): JsonResponse
    {
        $result = $this->healthService->check();

        if ($this->healthService->isDown($result)) {
            return ApiResult::failureWith($result, "Servicio no disponible.", 503)->toResponse();
        }

        return ApiResult::success($result)->toResponse();
    }
}
