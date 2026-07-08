<?php

namespace App\Services;

use App\Models\LoginAudit;
use App\Models\User;
use App\Repositories\LoginAuditRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LoginAuditService
{
    public function __construct(
        private readonly LoginAuditRepository $repository,
        private readonly UserAgentParser $userAgentParser,
    ) {}

    public function getAll(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function recordSuccess(User $user, Request $request, ?array $location = null): LoginAudit
    {
        return $this->repository->create([
            "user_id" => $user->id,
            "username" => $user->username,
            "status" => "success",
            "reason" => null,
            ...$this->metadataFrom($request),
            ...$this->locationFields($location),
        ]);
    }

    public function recordFailure(
        string $username,
        string $reason,
        ?User $user,
        Request $request,
        ?array $location = null,
    ): LoginAudit {
        return $this->repository->create([
            "user_id" => $user?->id,
            "username" => $username,
            "status" => "failed",
            "reason" => $reason,
            ...$this->metadataFrom($request),
            ...$this->locationFields($location),
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

    private function locationFields(?array $location): array
    {
        if (!$location) {
            return [];
        }

        return [
            "latitude" => $location["latitude"],
            "longitude" => $location["longitude"],
            "accuracy" => $location["accuracy"] ?? null,
        ];
    }

    private function metadataFrom(Request $request): array
    {
        $userAgent = $request->userAgent() ?? "";
        $os = $this->userAgentParser->parseOs($userAgent);

        return [
            "ip_address" => $request->ip(),
            "user_agent" => $userAgent,
            "os_name" => $os["name"],
            "os_version" => $os["version"],
            "device_type" => $this->userAgentParser->parseDeviceType($userAgent),
            "logged_at" => now(),
        ];
    }
}
