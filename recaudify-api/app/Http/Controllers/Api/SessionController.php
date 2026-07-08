<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Resources\SessionResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Services\ParameterService;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class SessionController extends ApiController
{
    public function __construct(
        private readonly UserSessionService $userSessions,
        private readonly ParameterService $parameterService,
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
        $filters = [
            "user_id" => $request->query("user_id"),
            "device_type" => $request->query("device_type"),
            "ip_address" => $request->query("ip_address"),
        ];

        $defaultPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_per_page");
        $maxPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_max_per_page");

        $perPage = (int) $request->query("per_page", (string) $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $this->userSessions->getAll($filters, $perPage);

        return ApiResult::paginated($paginator, SessionResource::collection($paginator->getCollection()))->toResponse();
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

    public function revokeAllGlobal(): JsonResponse
    {
        $this->userSessions->revokeAllGlobal();

        return ApiResult::empty("Todas las sesiones fueron cerradas correctamente.")->toResponse();
    }

    public function revokeAllForUser(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        /** @var User $currentUser */
        $currentUser = $this->guard()->user();
        $exceptSessionId = $user->id === $currentUser->id ? $this->currentSessionId() : null;

        $this->userSessions->revokeAllForUser($user, $exceptSessionId);

        return ApiResult::empty("Sesiones del usuario cerradas correctamente.")->toResponse();
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
