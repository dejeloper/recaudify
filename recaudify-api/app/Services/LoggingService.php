<?php

namespace App\Services;

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
        Log::channel("business")->info($event, $context);
    }

    public function logRequest(array $context): void
    {
        Log::channel("http")->info("HTTP request", $context);
    }

    public function logError(\Throwable $e, array $context = []): void
    {
        Log::channel("app-errors")->error($e->getMessage(), [
            ...$context,
            "exception" => get_class($e),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
            "trace" => $e->getTraceAsString(),
        ]);
    }

    public function logSecurity(string $event, array $context = []): void
    {
        Log::channel("security")->warning($event, $context);
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
