<?php

use App\Http\Responses\ApiResult;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__ . "/../routes/api.php", commands: __DIR__ . "/../routes/console.php", health: "/up")
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => null);

        $middleware->api(
            prepend: [\Illuminate\Http\Middleware\HandleCors::class, \App\Http\Middleware\SetJwtFromCookie::class],
        );

        $middleware->alias([
            "role" => \Spatie\Permission\Middleware\RoleMiddleware::class,
            "permission" => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            "role_or_permission" => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            "check.schedule" => \App\Http\Middleware\CheckUserSchedule::class,
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
    })
    ->create();
