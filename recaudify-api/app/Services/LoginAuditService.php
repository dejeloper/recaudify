<?php

namespace App\Services;

use App\Models\LoginAudit;
use App\Models\User;
use App\Repositories\LoginAuditRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class LoginAuditService
{
    public function __construct(private readonly LoginAuditRepository $repository) {}

    public function getAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function recordSuccess(User $user, Request $request): LoginAudit
    {
        return $this->repository->create([
            "user_id" => $user->id,
            "username" => $user->username,
            "status" => "success",
            "reason" => null,
            ...$this->metadataFrom($request),
        ]);
    }

    public function recordFailure(string $username, string $reason, ?User $user, Request $request): LoginAudit
    {
        return $this->repository->create([
            "user_id" => $user?->id,
            "username" => $username,
            "status" => "failed",
            "reason" => $reason,
            ...$this->metadataFrom($request),
        ]);
    }

    public function attachLocation(User $user, array $coords): void
    {
        $audit = $this->repository->findLatestSuccessToday($user);

        $audit?->update([
            "latitude" => $coords["latitude"],
            "longitude" => $coords["longitude"],
            "accuracy" => $coords["accuracy"] ?? null,
        ]);
    }

    private function metadataFrom(Request $request): array
    {
        $userAgent = $request->userAgent() ?? "";
        $os = $this->parseOs($userAgent);

        return [
            "ip_address" => $request->ip(),
            "user_agent" => $userAgent,
            "os_name" => $os["name"],
            "os_version" => $os["version"],
            "device_type" => $this->parseDeviceType($userAgent),
            "logged_at" => now(),
        ];
    }

    private function parseOs(string $ua): array
    {
        $rules = [
            "/Windows NT ([\d.]+)/" => "Windows",
            "/iPhone OS ([\d_]+)/" => "iOS",
            "/iPad.*OS ([\d_]+)/" => "iPadOS",
            "/Android ([\d.]+)/" => "Android",
            "/Mac OS X ([\d_]+)/" => "macOS",
            "/Linux/" => "Linux",
        ];

        foreach ($rules as $regex => $name) {
            if (preg_match($regex, $ua, $m)) {
                return ["name" => $name, "version" => str_replace("_", ".", $m[1] ?? "")];
            }
        }

        return ["name" => "Unknown", "version" => ""];
    }

    private function parseDeviceType(string $ua): string
    {
        if (preg_match("/iPad|Android(?!.*Mobile)|Tablet/i", $ua)) {
            return "tablet";
        }

        if (preg_match("/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i", $ua)) {
            return "mobile";
        }

        return "desktop";
    }
}
