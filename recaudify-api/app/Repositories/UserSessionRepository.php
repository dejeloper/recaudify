<?php

namespace App\Repositories;

use App\Models\UserSession;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserSessionRepository
{
    public function create(array $data): UserSession
    {
        return UserSession::create($data);
    }

    public function find(int $id): ?UserSession
    {
        return UserSession::find($id);
    }

    public function findForUser(int $userId, int $id): ?UserSession
    {
        return UserSession::where("user_id", $userId)->where("id", $id)->first();
    }

    public function findActiveBySessionId(string $sessionId): ?UserSession
    {
        return UserSession::active()->where("session_id", $sessionId)->first();
    }

    public function activeForUser(int $userId): Collection
    {
        return UserSession::active()->where("user_id", $userId)->orderByDesc("last_used_at")->get();
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return UserSession::active()
            ->with("user")
            ->when($filters["user_id"] ?? null, fn($q, $v) => $q->where("user_id", $v))
            ->when($filters["device_type"] ?? null, fn($q, $v) => $q->where("device_type", $v))
            ->when($filters["ip_address"] ?? null, fn($q, $v) => $q->where("ip_address", "like", "%{$v}%"))
            ->orderByDesc("last_used_at")
            ->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return UserSession::active()->get();
    }
}
