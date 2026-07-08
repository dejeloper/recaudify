<?php

namespace App\Services;

use App\Enums\ParameterType;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginLockoutService
{
    public function __construct(private readonly ParameterService $parameterService) {}

    public function isEnabled(): bool
    {
        return (bool) ($this->parameterService->get(ParameterType::Security, "lockout_enabled") ?? true);
    }

    public function maxAttempts(): int
    {
        return (int) ($this->parameterService->get(ParameterType::Security, "max_login_attempts") ?? 5);
    }

    public function lockoutMinutes(): int
    {
        return (int) ($this->parameterService->get(ParameterType::Security, "lockout_minutes") ?? 15);
    }

    public function isLocked(string $username, string $ip): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $maxAttempts = $this->maxAttempts();

        return RateLimiter::tooManyAttempts($this->userKey($username), $maxAttempts) ||
            RateLimiter::tooManyAttempts($this->ipKey($ip), $maxAttempts);
    }

    public function secondsRemaining(string $username, string $ip): int
    {
        return max(RateLimiter::availableIn($this->userKey($username)), RateLimiter::availableIn($this->ipKey($ip)));
    }

    public function recordFailedAttempt(string $username, string $ip): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $decaySeconds = $this->lockoutMinutes() * 60;

        RateLimiter::hit($this->userKey($username), $decaySeconds);
        RateLimiter::hit($this->ipKey($ip), $decaySeconds);
    }

    public function clear(string $username, string $ip): void
    {
        RateLimiter::clear($this->userKey($username));
        RateLimiter::clear($this->ipKey($ip));
    }

    private function userKey(string $username): string
    {
        return "login-lockout:user:" . Str::lower(trim($username));
    }

    private function ipKey(string $ip): string
    {
        return "login-lockout:ip:{$ip}";
    }
}
