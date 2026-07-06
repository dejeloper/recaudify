<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginLocationRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\AuthService;
use App\Services\LoginAuditService;
use App\Services\ParameterService;
use App\Services\PasswordPolicyService;
use App\Services\PasswordResetService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly LoginAuditService $loginAudit,
        private readonly ParameterService $parameterService,
        private readonly PasswordPolicyService $passwordPolicy,
        private readonly PasswordResetService $passwordResetService,
        private readonly UserService $userService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

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
        $loginField = $this->authService->getLoginField();

        $credentials = [
            $loginField => $request->username,
            "password" => $request->password,
        ];

        $location = $request->filled("latitude") ? $request->only("latitude", "longitude", "accuracy") : null;

        if (!($token = $this->guard()->attempt($credentials))) {
            $attempted = $this->userService->findByLoginField($loginField, $request->username);
            $this->loginAudit->recordFailure(
                $request->username,
                "invalid_credentials",
                $attempted,
                $request,
                $location,
            );

            return ApiResult::unauthorized("Credenciales incorrectas.")->toResponse();
        }

        /** @var User $user */
        $user = $this->guard()->user();

        if (!$user->active) {
            $this->guard()->logout();
            $this->loginAudit->recordFailure($user->username, "inactive", $user, $request, $location);

            return ApiResult::forbidden("Usuario inactivo.")->toResponse();
        }

        $error = $this->authService->getScheduleAccessError($user);

        if ($error !== null) {
            $this->guard()->logout();
            $this->loginAudit->recordFailure($user->username, "out_of_schedule", $user, $request, $location);

            return ApiResult::forbidden($error)->toResponse();
        }

        $this->loginAudit->recordSuccess($user, $request, $location);

        return $this->buildTokenResponse($token, $user);
    }

    private function buildTokenResponse(string $token, User $user): JsonResponse
    {
        $response = ApiResult::success(
            [
                "token" => $token,
                "token_type" => "bearer",
                "expires_in" => config("jwt.ttl") * 60,
                "user" => $this->userPayload($user),
            ],
            "Sesion iniciada correctamente.",
        )
            ->toResponse()
            ->withCookie($this->tokenCookie($token));

        $response->headers->set("Authorization", "Bearer {$token}");

        return $response;
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $this->passwordResetService->changeOwnPassword(
            $user,
            $request->string("current_password")->toString(),
            $request->string("password")->toString(),
        );

        return ApiResult::empty("Contraseña actualizada correctamente.")->toResponse();
    }

    public function loginLocation(LoginLocationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $this->loginAudit->attachLocation($user, $request->validated());

        return ApiResult::empty("Ubicacion registrada.")->toResponse();
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        return ApiResult::success($this->userPayload($user))->toResponse();
    }

    private function userPayload(User $user): array
    {
        $auth = ParameterType::Authentication;
        $data = (new UserResource($user))->toArray(request());

        $data["current_shift"] = $this->authService->getCurrentShift($user);
        $data["shift_status_enabled"] = $this->parameterService->get($auth, "shift_status_enabled");
        $data["shift_countdown_enabled"] = $this->parameterService->get($auth, "shift_countdown_enabled");
        $data["geolocalization_login_enabled"] = $this->parameterService->get($auth, "geolocalization_login_enabled");
        $data["ip_address"] = request()->ip();
        $data["password_expired"] = $this->passwordPolicy->isExpired($user);

        return $data;
    }

    public function config(): JsonResponse
    {
        return ApiResult::success([
            "geolocalization_login" => $this->parameterService->get(
                ParameterType::Authentication,
                "geolocalization_login_enabled",
            ),
            "login_field" => $this->authService->getLoginField(),
            "password_policy" => $this->passwordPolicy->config(),
        ])->toResponse();
    }

    public function refresh(): JsonResponse
    {
        try {
            $newToken = $this->guard()->refresh();
        } catch (\Throwable) {
            return ApiResult::unauthorized("No se pudo renovar la sesion.")->toResponse();
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

        return ApiResult::empty("Sesion cerrada correctamente.")
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
