<?php

namespace App\Services;

use App\Enums\ParameterType;
use App\Models\User;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(
        private readonly ParameterService $parameterService,
        private readonly UserService $userService,
        private readonly LoggingService $logging,
    ) {}

    public function reset(User $user, ?int $performedByUserId): string
    {
        $password = $this->generatePassword();

        $this->userService->update($user, ["password" => $password]);

        $this->logging->logSecurity("Contraseña reseteada por administrador", [
            "user_id" => $user->id,
            "by_user_id" => $performedByUserId,
        ]);

        return $password;
    }

    private function generatePassword(): string
    {
        $mode = $this->parameterService->get(ParameterType::Authentication, "reset_password_mode");

        if ($mode === "fixed") {
            $fixed = $this->parameterService->get(ParameterType::Authentication, "reset_password_fixed_value");

            if (filled($fixed)) {
                return $fixed;
            }
        }

        return Str::password(12);
    }
}
