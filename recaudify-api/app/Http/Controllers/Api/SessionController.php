<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SessionResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\UserService;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class SessionController extends ApiController
{
    public function __construct(
        private readonly UserSessionService $userSessions,
        private readonly UserService $userService,
    ) {}

    public function mine(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();
        $currentSessionId = $this->currentSessionId();

        $sessions = $this->userSessions->forUser($user->id)->each(function ($session) use ($currentSessionId) {
            $session->is_current = $session->session_id === $currentSessionId;
        });

        return ApiResult::success(SessionResource::collection($sessions))->toResponse();
    }

    public function revokeMine(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();
        $session = $this->userSessions->findOwned($user->id, $id);

        if (!$session) {
            return ApiResult::notFound("Sesion no encontrada.")->toResponse();
        }

        $this->userSessions->revoke($session);

        return ApiResult::empty("Sesion revocada correctamente.")->toResponse();
    }

    public function revokeAllMine(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        $this->userSessions->revokeAllForUser($user, $this->currentSessionId());

        return ApiResult::empty("Sesiones cerradas correctamente.")->toResponse();
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->query("user_id");

        if (!$userId || !$this->userService->find($userId)) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $sessions = $this->userSessions->forUser($userId)->load("user");

        return ApiResult::success(SessionResource::collection($sessions))->toResponse();
    }

    public function revoke(int $id): JsonResponse
    {
        $session = $this->userSessions->find($id);

        if (!$session) {
            return ApiResult::notFound("Sesion no encontrada.")->toResponse();
        }

        $this->userSessions->revoke($session);

        return ApiResult::empty("Sesion revocada correctamente.")->toResponse();
    }

    private function currentSessionId(): ?string
    {
        try {
            return $this->guard()->getPayload()->get("session_id");
        } catch (\Throwable) {
            return null;
        }
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth("api");
    }
}
