<?php

namespace App\Services;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class LoggingService
{
    private const SENSITIVE_FIELDS = [
        "password",
        "password_confirmation",
        "token",
        "access_token",
        "refresh_token",
        "authorization",
        "secret",
        "api_key",
        "credit_card",
        "cvv",
        "pin",
        "key",
    ];

    public function logBusiness(string $event, array $context = []): void
    {
        Log::channel("business")->info($event, $this->withRequestId($context));
    }

    public function logRequest(array $context): void
    {
        Log::channel("http")->info("HTTP request", $this->withRequestId($context));
    }

    public function logError(\Throwable $e, array $context = []): void
    {
        Log::channel("app-errors")->error(
            $e->getMessage(),
            $this->withRequestId([
                ...$context,
                "exception" => get_class($e),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString(),
            ]),
        );
    }

    public function logSecurity(string $event, array $context = []): void
    {
        Log::channel("security")->warning($event, $this->withRequestId($context));
    }

    /**
     * Sella cada línea con el id de la petición.
     *
     * Es lo que permite juntar después lo que quedó repartido en cuatro archivos distintos.
     */
    private function withRequestId(array $context): array
    {
        $requestId = Context::get(AssignRequestId::CONTEXT_KEY);

        return $requestId ? [AssignRequestId::CONTEXT_KEY => $requestId, ...$context] : $context;
    }

    public function maskSensitive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitive($value);
            } elseif (in_array(strtolower((string) $key), self::SENSITIVE_FIELDS, true)) {
                $data[$key] = "***";
            }
        }

        return $data;
    }
}
