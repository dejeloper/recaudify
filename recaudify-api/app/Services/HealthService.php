<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Diagnóstico de las dependencias de las que depende la aplicación para funcionar.
 *
 * Pensado para un chequeo externo de uptime: responde qué está caído, sin exponer detalles del
 * error hacia afuera (el detalle va al canal de logs, no a la respuesta).
 */
class HealthService
{
    public const STATUS_OK = "ok";
    public const STATUS_DEGRADED = "degraded";
    public const STATUS_DOWN = "down";

    /** Sin estas la aplicación no puede operar: si fallan, el endpoint responde 503. */
    private const CRITICAL = ["database", "cache", "storage"];

    public function __construct(private readonly LoggingService $logging) {}

    public function check(): array
    {
        $checks = [
            "database" => $this->measure(fn() => $this->checkDatabase()),
            "cache" => $this->measure(fn() => $this->checkCache()),
            "storage" => $this->measure(fn() => $this->checkStorage()),
            "queue" => $this->measure(fn() => $this->checkQueue()),
        ];

        return [
            "status" => $this->overallStatus($checks),
            "checks" => $checks,
            "timestamp" => now()->toIso8601String(),
        ];
    }

    public function isDown(array $result): bool
    {
        return $result["status"] === self::STATUS_DOWN;
    }

    /**
     * Ejecuta un chequeo midiendo lo que tarda y sin dejar que una excepción tumbe el endpoint:
     * un health check que revienta no sirve para diagnosticar nada.
     */
    private function measure(callable $check): array
    {
        $startedAt = microtime(true);

        try {
            $result = $check();
        } catch (Throwable $e) {
            $this->logging->logError($e, ["context" => "health_check"]);

            $result = ["status" => self::STATUS_DOWN];
        }

        $result["duration_ms"] = (int) round((microtime(true) - $startedAt) * 1000);

        return $result;
    }

    private function checkDatabase(): array
    {
        DB::connection()->getPdo();
        DB::select("select 1");

        return ["status" => self::STATUS_OK];
    }

    private function checkCache(): array
    {
        $key = "health_check_" . Str::random(8);
        Cache::put($key, true, 5);
        $stored = Cache::get($key) === true;
        Cache::forget($key);

        return ["status" => $stored ? self::STATUS_OK : self::STATUS_DOWN];
    }

    private function checkStorage(): array
    {
        $disk = Storage::disk("local");
        $file = "health/check_" . Str::random(8) . ".txt";

        $disk->put($file, "ok");
        $written = $disk->get($file) === "ok";
        $disk->delete($file);

        return ["status" => $written ? self::STATUS_OK : self::STATUS_DOWN];
    }

    /**
     * Una cola con trabajo acumulado no es una caída: es una señal. Por eso los jobs fallidos
     * marcan "degradado" y no tumban el chequeo.
     */
    private function checkQueue(): array
    {
        $connection = config("queue.default");

        if ($connection !== "database") {
            return ["status" => self::STATUS_OK, "driver" => $connection];
        }

        $pending = DB::table("jobs")->count();
        $failed = DB::table("failed_jobs")->count();

        return [
            "status" => $failed > 0 ? self::STATUS_DEGRADED : self::STATUS_OK,
            "driver" => $connection,
            "pending" => $pending,
            "failed" => $failed,
        ];
    }

    private function overallStatus(array $checks): string
    {
        foreach (self::CRITICAL as $name) {
            if (($checks[$name]["status"] ?? null) === self::STATUS_DOWN) {
                return self::STATUS_DOWN;
            }
        }

        foreach ($checks as $check) {
            if ($check["status"] !== self::STATUS_OK) {
                return self::STATUS_DEGRADED;
            }
        }

        return self::STATUS_OK;
    }
}
