<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\AuthService;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return ApiResult::created(
            [
                "id" => $user->id,
                "name" => $user->name,
                "username" => $user->username,
            ],
            "Usuario registrado correctamente.",
        )->toResponse();
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            "username" => $request->username,
            "password" => $request->password,
        ];

        if (!($token = $this->guard()->attempt($credentials))) {
            return ApiResult::unauthorized("Credenciales incorrectas.")->toResponse();
        }

        /** @var User $user */
        $user = $this->guard()->user();

        if (!$user->active) {
            $this->guard()->logout();

            return ApiResult::forbidden("Usuario inactivo.")->toResponse();
        }

        $error = $this->authService->getScheduleAccessError($user);

        if ($error !== null) {
            $this->guard()->logout();

            return ApiResult::forbidden($error)->toResponse();
        }

        return $this->buildTokenResponse($token, $user);
    }

    private function buildTokenResponse(string $token, User $user): JsonResponse
    {
        $response = ApiResult::success(
            [
                "token" => $token,
                "token_type" => "bearer",
                "expires_in" => config("jwt.ttl") * 60,
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "username" => $user->username,
                    "role" => $user->getRoleNames()->first(),
                ],
            ],
            "Sesión iniciada correctamente.",
        )
            ->toResponse()
            ->withCookie($this->tokenCookie($token));

        $response->headers->set("Authorization", "Bearer {$token}");

        return $response;
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $resource = new UserResource($user);
        $data = $resource->toArray(request());

        $data["current_shift"] = $this->authService->getCurrentShift($user);
        $data["shift_status_enabled"] = ParameterService::get("shift-status", "true") === "true";
        $data["shift_countdown_enabled"] = ParameterService::get("shift-status-countdown", "true") === "true";
        $data["ip_address"] = request()->ip();

        return ApiResult::success($data)->toResponse();
    }

    public function refresh(): JsonResponse
    {
        try {
            $newToken = $this->guard()->refresh();
        } catch (\Throwable) {
            return ApiResult::unauthorized("No se pudo renovar la sesión.")->toResponse();
        }

        $response = ApiResult::success(
            [
                "token" => $newToken,
                "token_type" => "bearer",
                "expires_in" => config("jwt.ttl") * 60,
            ],
            "Token renovado.",
        )
            ->toResponse()
            ->withCookie($this->tokenCookie($newToken));

        $response->headers->set("Authorization", "Bearer {$newToken}");

        return $response;
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return ApiResult::empty("Sesión cerrada correctamente.")
            ->toResponse()
            ->withCookie(Cookie::forget(config("jwt.cookie_key_name", "token")));
    }

    private function tokenCookie(string $token): SymfonyCookie
    {
        $minutes = (int) config("jwt.refresh_ttl");
        $isLocal = app()->environment("local");

        return cookie(
            name: config("jwt.cookie_key_name", "token"),
            value: $token,
            minutes: $minutes,
            path: "/",
            secure: !$isLocal,
            httpOnly: true,
            sameSite: $isLocal ? "Lax" : "None",
        );
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth("api");
    }
}
