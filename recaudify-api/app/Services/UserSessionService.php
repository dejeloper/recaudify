<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Repositories\UserSessionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserSessionService
{
    private const TOUCH_THRESHOLD_MINUTES = 1;

    public function __construct(
        private readonly UserSessionRepository $repository,
        private readonly UserAgentParser $userAgentParser,
    ) {}

    public function create(User $user, string $sessionId, Request $request): UserSession
    {
        $userAgent = $request->userAgent() ?? "";
        $os = $this->userAgentParser->parseOs($userAgent);

        return $this->repository->create([
            "user_id" => $user->id,
            "session_id" => $sessionId,
            "ip_address" => $request->ip(),
            "user_agent" => $userAgent,
            "os_name" => $os["name"],
            "os_version" => $os["version"],
            "device_type" => $this->userAgentParser->parseDeviceType($userAgent),
            "last_used_at" => now(),
            "expires_at" => now()->addMinutes((int) config("jwt.refresh_ttl")),
        ]);
    }

    public function findActive(string $sessionId): ?UserSession
    {
        return $this->repository->findActiveBySessionId($sessionId);
    }

    public function find(int $id): ?UserSession
    {
        return $this->repository->find($id);
    }

    public function findOwned(int $userId, int $id): ?UserSession
    {
        return $this->repository->findForUser($userId, $id);
    }

    public function touch(UserSession $session): void
    {
        if (
            $session->last_used_at !== null &&
            $session->last_used_at->diffInMinutes(now()) < self::TOUCH_THRESHOLD_MINUTES
        ) {
            return;
        }

        $session->update(["last_used_at" => now()]);
    }

    public function revoke(UserSession $session): void
    {
        $session->update(["revoked_at" => now()]);
        Cache::forget($this->activeCacheKey($session->session_id));
    }

    public function revokeAllForUser(User $user, ?string $exceptSessionId = null): void
    {
        foreach ($this->repository->activeForUser($user->id) as $session) {
            if ($exceptSessionId !== null && $session->session_id === $exceptSessionId) {
                continue;
            }

            $this->revoke($session);
        }
    }

    public function forUser(int $userId): Collection
    {
        return $this->repository->activeForUser($userId);
    }

    public function activeCacheKey(string $sessionId): string
    {
        return "session-active:{$sessionId}";
    }
}
