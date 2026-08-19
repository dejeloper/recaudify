<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckUserSchedule;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\LogHttpRequests;
use App\Http\Middleware\SetJwtFromCookie;
use App\Http\Middleware\TrackUserSession;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__ . "/../routes/api.php", commands: __DIR__ . "/../routes/console.php", health: "/up")
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => null);

        $middleware->api(prepend: [HandleCors::class, SetJwtFromCookie::class, CheckMaintenanceMode::class]);
        $middleware->api(append: [LogHttpRequests::class]);

        $middleware->alias([
            "role" => RoleMiddleware::class,
            "permission" => PermissionMiddleware::class,
            "role_or_permission" => RoleOrPermissionMiddleware::class,
            "check.schedule" => CheckUserSchedule::class,
            "force.password.change" => ForcePasswordChange::class,
            "track.session" => TrackUserSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn(Request $request) => $request->is("api/*"));

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is("api/*")) {
                return ApiResult::unauthorized("No autenticado.")->toResponse();
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is("api/*")) {
                return ApiResult::validationError($e->errors())->toResponse();
            }
        });

        $exceptions->reportable(function (Throwable $e) {
            $logging = app(LoggingService::class);

            if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
                $logging->logSecurity($e->getMessage(), [
                    "exception" => get_class($e),
                    "file" => $e->getFile(),
                    "line" => $e->getLine(),
                ]);

                return;
            }

            if (!($e instanceof ValidationException)) {
                $logging->logError($e);
            }
        });
    })
    ->create();
