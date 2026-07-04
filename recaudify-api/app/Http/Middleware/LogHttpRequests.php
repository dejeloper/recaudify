<?php

namespace App\Http\Middleware;

use App\Services\LoggingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogHttpRequests
{
    private const STARTED_AT_ATTRIBUTE = "_log_http_started_at";

    public function __construct(private readonly LoggingService $logging) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::STARTED_AT_ATTRIBUTE, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();
        $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE, microtime(true));
        $context = [
            "method" => $request->method(),
            "path" => $request->path(),
            "status" => $status,
            "duration_ms" => (int) round((microtime(true) - $startedAt) * 1000),
            "user_id" => $request->user()?->id,
            "ip" => $request->ip(),
            "input" => $this->logging->maskSensitive($request->all()),
        ];

        if ($status === 401 || $status === 403) {
            $this->logging->logSecurity("Acceso denegado", $context);

            return;
        }

        if ($status >= 500) {
            $context["response"] = $response->getContent();
        }

        $this->logging->logRequest($context);
    }
}
