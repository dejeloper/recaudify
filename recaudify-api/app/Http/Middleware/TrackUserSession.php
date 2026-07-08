<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResult;
use App\Services\UserSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    private const CACHE_TTL_SECONDS = 30;

    public function __construct(private readonly UserSessionService $userSessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $sessionId = $this->guard()->payload()?->get("session_id");
        } catch (\Throwable) {
            $sessionId = null;
        }

        // Tokens emitidos antes de esta funcionalidad (o sin token parseable, ej. en tests con
        // actingAs()) no tienen el claim: se dejan pasar, no hay forma de reconstruir su sesión
        // retroactivamente.
        if (!$sessionId) {
            return $next($request);
        }

        $cacheKey = $this->userSessions->activeCacheKey($sessionId);

        // Si ya sabemos (por caché) que sigue activa, no hace falta tocar la BD en cada
        // request; el "last_used_at" queda con el rezago del TTL del caché, que es aceptable.
        if (Cache::has($cacheKey)) {
            if (!Cache::get($cacheKey)) {
                return ApiResult::unauthorized("Sesion revocada.")->toResponse();
            }

            return $next($request);
        }

        $session = $this->userSessions->findActive($sessionId);
        Cache::put($cacheKey, $session !== null, self::CACHE_TTL_SECONDS);

        if (!$session) {
            return ApiResult::unauthorized("Sesion revocada.")->toResponse();
        }

        $this->userSessions->touch($session);

        return $next($request);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth("api");
    }
}
