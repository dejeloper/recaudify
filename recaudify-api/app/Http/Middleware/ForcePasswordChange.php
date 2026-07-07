<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\PasswordPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function __construct(private readonly PasswordPolicyService $passwordPolicy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth("api")->user();

        if (!($user instanceof User)) {
            return $next($request);
        }

        if ($this->passwordPolicy->isExpired($user)) {
            return ApiResult::failure("La contraseña ha expirado. Debe cambiarla para continuar.", 423)->toResponse();
        }

        return $next($request);
    }
}
