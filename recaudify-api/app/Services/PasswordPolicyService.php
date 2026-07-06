<?php

namespace App\Services;

use App\Enums\ParameterType;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

class PasswordPolicyService
{
    public function __construct(private readonly ParameterService $parameterService) {}

    public function rule(): Password
    {
        $rule = Password::min($this->minLength());

        if ($this->requiresUppercase()) {
            $rule = $rule->mixedCase();
        }

        if ($this->requiresNumbers()) {
            $rule = $rule->numbers();
        }

        if ($this->requiresSymbols()) {
            $rule = $rule->symbols();
        }

        return $rule;
    }

    public function minLength(): int
    {
        return (int) ($this->parameterService->get(ParameterType::Security, "password_min_length") ?? 8);
    }

    public function requiresUppercase(): bool
    {
        return (bool) $this->parameterService->get(ParameterType::Security, "password_require_uppercase");
    }

    public function requiresNumbers(): bool
    {
        return (bool) $this->parameterService->get(ParameterType::Security, "password_require_numbers");
    }

    public function requiresSymbols(): bool
    {
        return (bool) $this->parameterService->get(ParameterType::Security, "password_require_symbols");
    }

    public function expirationDays(): int
    {
        return (int) ($this->parameterService->get(ParameterType::Security, "password_expiration_days") ?? 0);
    }

    public function isExpired(User $user): bool
    {
        $days = $this->expirationDays();

        if ($days <= 0 || !$user->password_changed_at) {
            return false;
        }

        return $user->password_changed_at->addDays($days)->isPast();
    }

    public function config(): array
    {
        return [
            "min_length" => $this->minLength(),
            "require_uppercase" => $this->requiresUppercase(),
            "require_numbers" => $this->requiresNumbers(),
            "require_symbols" => $this->requiresSymbols(),
        ];
    }
}
