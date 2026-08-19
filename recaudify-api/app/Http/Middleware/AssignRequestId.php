<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Asigna un identificador único a cada petición.
 *
 * Los cuatro canales de log (`http`, `business`, `security`, `app-errors`) escriben por separado:
 * sin un hilo común, seguir un problema puntual obliga a adivinar por timestamp. Con esto, el
 * usuario reporta un id y se ve todo lo que ocurrió en esa petición.
 */
class AssignRequestId
{
    public const HEADER = "X-Request-Id";

    public const CONTEXT_KEY = "request_id";

    /**
     * Formato aceptado para un id que viene de afuera.
     *
     * Se valida a propósito: el id termina escrito en los archivos de log, y un valor con saltos de
     * línea permitiría inyectar entradas falsas.
     */
    private const VALID_FORMAT = '/^[A-Za-z0-9._-]{8,64}$/';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->resolveId($request);

        Context::add(self::CONTEXT_KEY, $id);
        $request->headers->set(self::HEADER, $id);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    /** Respeta el id que ya traiga la petición (proxy, frontend) si es confiable; si no, genera uno. */
    private function resolveId(Request $request): string
    {
        $incoming = $request->header(self::HEADER);

        if (is_string($incoming) && preg_match(self::VALID_FORMAT, $incoming) === 1) {
            return $incoming;
        }

        return (string) Str::uuid();
    }
}
