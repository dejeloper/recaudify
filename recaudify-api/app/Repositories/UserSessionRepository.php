<?php

namespace App\Repositories;

use App\Models\UserSession;
use Illuminate\Database\Eloquent\Collection;

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
}
