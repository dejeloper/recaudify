<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class AuthController extends ApiController
{
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

        if ($user->hasRole("superadmin")) {
            return $this->buildTokenResponse($token, $user);
        }

        $schedules = $user->schedules()->get();

        if ($schedules->isEmpty()) {
            $this->guard()->logout();

            return ApiResult::forbidden("No tiene horario de acceso asignado.")->toResponse();
        }

        $now = now();
        $currentTime = $now->format("H:i");
        $allowed = $schedules
            ->where("day_of_week", $now->dayOfWeek)
            ->contains(
                fn($s) => substr($s->start_time, 0, 5) <= $currentTime && $currentTime <= substr($s->end_time, 0, 5),
            );

        if (!$allowed) {
            $this->guard()->logout();

            return ApiResult::forbidden("Acceso fuera del horario permitido.")->toResponse();
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

        if ($user->hasRole("superadmin")) {
            $data["current_shift"] = [
                "is_within_schedule" => true,
                "show_status" => true,
                "day_of_week" => now()->dayOfWeek,
                "start_time" => null,
                "end_time" => null,
                "remaining_minutes" => null,
            ];
        } else {
            $now = now();
            $currentTime = $now->format("H:i");
            $dayOfWeek = $now->dayOfWeek;

            $schedules = $user->schedules()->get();
            $currentSchedule = $schedules
                ->where("day_of_week", $dayOfWeek)
                ->first(
                    fn($s) => substr($s->start_time, 0, 5) <= $currentTime &&
                        $currentTime <= substr($s->end_time, 0, 5),
                );

            $data["current_shift"] = [
                "is_within_schedule" => $currentSchedule !== null,
                "show_status" => $currentSchedule ? (bool) $currentSchedule->show_status : false,
                "day_of_week" => $dayOfWeek,
                "start_time" => $currentSchedule ? substr($currentSchedule->start_time, 0, 5) : null,
                "end_time" => $currentSchedule ? substr($currentSchedule->end_time, 0, 5) : null,
                "remaining_minutes" => $currentSchedule
                    ? (int) ((strtotime(substr($currentSchedule->end_time, 0, 5)) - strtotime($currentTime)) / 60)
                    : null,
            ];
        }

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
