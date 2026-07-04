<?php

namespace App\Repositories;

use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class LoginAuditRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return LoginAudit::with("user")
            ->when($filters["user_id"] ?? null, fn($q, $v) => $q->where("user_id", $v))
            ->when($filters["status"] ?? null, fn($q, $v) => $q->where("status", $v))
            ->orderByDesc("id")
            ->paginate($perPage);
    }

    public function create(array $data): LoginAudit
    {
        return LoginAudit::create($data);
    }

    public function findLatestSuccessToday(User $user): ?LoginAudit
    {
        return LoginAudit::where("user_id", $user->id)
            ->where("status", "success")
            ->whereDate("logged_at", now()->toDateString())
            ->latest("id")
            ->first();
    }
}
