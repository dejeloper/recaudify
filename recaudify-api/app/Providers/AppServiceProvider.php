<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public const SUPERADMIN_ROLE = "superadmin";

    public function register(): void {}

    public function boot(): void
    {
        Gate::before(function ($user) {
            if ($user->hasRole(self::SUPERADMIN_ROLE)) {
                return true;
            }
        });
    }
}
