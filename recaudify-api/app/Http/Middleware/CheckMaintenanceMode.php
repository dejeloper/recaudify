<?php

namespace App\Http\Middleware;

use App\Enums\ParameterType;
use App\Http\Responses\ApiResult;
use App\Services\ParameterService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Pausa operativa activada desde Parámetros.
 *
 * No reemplaza a `php artisan down`: aquel apaga la aplicación entera para desplegar, este permite
 * que un administrador detenga la operación desde el navegador y siga trabajando él.
 */
class CheckMaintenanceMode
{
    public const SCOPE_ALL = "all";
    public const SCOPE_WRITES = "writes";

    public const BYPASS_PERMISSION = "maintenance.bypass";

    /**
     * Rutas que siguen respondiendo en mantenimiento.
     *
     * El health check, porque el monitor externo tiene que poder distinguir "en mantenimiento" de
     * "caído". Y las de sesión, para que alguien pueda entrar y ver el aviso en vez de chocar con
     * un login que rechaza credenciales correctas.
     */
    private const EXEMPT_PATHS = ["api/health", "api/auth/*", "up"];

    private const WRITE_METHODS = ["POST", "PUT", "PATCH", "DELETE"];

    public function __construct(private readonly ParameterService $parameters) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isEnabled() || $request->is(...self::EXEMPT_PATHS)) {
            return $next($request);
        }

        if ($this->scope() === self::SCOPE_WRITES && !$this->isWrite($request)) {
            return $next($request);
        }

        if ($this->userCanBypass()) {
            return $next($request);
        }

        return ApiResult::failureWith(
            ["maintenance" => true, "scope" => $this->scope()],
            $this->message(),
            503,
        )->toResponse();
    }

    /**
     * Si los parámetros no se pueden leer, el sistema queda abierto.
     *
     * Es deliberado: un fallo de caché o de base no debe dejar a todo el mundo afuera sin que nadie
     * lo haya decidido, y sin forma de entrar a apagarlo.
     */
    private function isEnabled(): bool
    {
        try {
            return (bool) $this->parameters->get(ParameterType::Configuration, "maintenance_mode");
        } catch (Throwable) {
            return false;
        }
    }

    private function scope(): string
    {
        try {
            $scope = $this->parameters->get(ParameterType::Configuration, "maintenance_scope");
        } catch (Throwable) {
            return self::SCOPE_ALL;
        }

        return $scope === self::SCOPE_WRITES ? self::SCOPE_WRITES : self::SCOPE_ALL;
    }

    private function message(): string
    {
        $default = "El sistema está en mantenimiento. Intente de nuevo en unos minutos.";

        try {
            $message = $this->parameters->get(ParameterType::Configuration, "maintenance_message");
        } catch (Throwable) {
            return $default;
        }

        return is_string($message) && trim($message) !== "" ? $message : $default;
    }

    private function isWrite(Request $request): bool
    {
        return in_array($request->method(), self::WRITE_METHODS, true);
    }

    /** El guard por defecto es `web`; acá el usuario siempre viene del JWT. */
    private function userCanBypass(): bool
    {
        try {
            return (bool) Auth::guard("api")->user()?->can(self::BYPASS_PERMISSION);
        } catch (Throwable) {
            return false;
        }
    }
}
