<?php

namespace App\Services;

use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class LoginAuditService
{
    public const DEFAULT_PER_PAGE = 25;

    public const MAX_PER_PAGE = 100;

    /**
     * Listado paginado de accesos. Filtros: user_id, status.
     */
    public function getAll(array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return LoginAudit::with("user")
            ->when($filters["user_id"] ?? null, fn($q, $v) => $q->where("user_id", $v))
            ->when($filters["status"] ?? null, fn($q, $v) => $q->where("status", $v))
            ->orderByDesc("id")
            ->paginate($perPage);
    }

    public function recordSuccess(User $user, Request $request): LoginAudit
    {
        return LoginAudit::create([
            "user_id" => $user->id,
            "username" => $user->username,
            "status" => "success",
            "reason" => null,
            ...$this->metadataFrom($request),
        ]);
    }

    public function recordFailure(string $username, string $reason, ?User $user, Request $request): LoginAudit
    {
        return LoginAudit::create([
            "user_id" => $user?->id,
            "username" => $username,
            "status" => "failed",
            "reason" => $reason,
            ...$this->metadataFrom($request),
        ]);
    }

    /**
     * Enriquece el último acceso exitoso del usuario (de hoy) con la geolocalización
     * provista por el cliente. La IP/UA ya las fijó el servidor en el login.
     */
    public function attachLocation(User $user, array $coords): void
    {
        $audit = LoginAudit::where("user_id", $user->id)
            ->where("status", "success")
            ->whereDate("logged_at", now()->toDateString())
            ->latest("id")
            ->first();

        $audit?->update([
            "latitude" => $coords["latitude"],
            "longitude" => $coords["longitude"],
            "accuracy" => $coords["accuracy"] ?? null,
        ]);
    }

    /**
     * Metadata tomada del request (autoritativa del servidor): IP, user-agent
     * y derivados (SO y tipo de dispositivo).
     */
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
