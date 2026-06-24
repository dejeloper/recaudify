<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResult;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSchedule
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth("api")->user();

        if (!($user instanceof User)) {
            return $next($request);
        }

        if ($user->hasRole("superadmin")) {
            return $next($request);
        }

        $schedules = $user->schedules()->get();

        if ($schedules->isEmpty()) {
            return ApiResult::forbidden("No tiene horario de acceso asignado.")->toResponse();
        }

        $now = now();
        $currentTime = $now->format("H:i");
        $allowed = $schedules
            ->where("day_of_week", $now->dayOfWeek)
            ->contains(
                fn($s) => substr($s->start_time, 0, 5) <= $currentTime && $currentTime <= substr($s->end_time, 0, 5),
            );

        if (!$allowed) {
            return ApiResult::forbidden("Acceso fuera del horario permitido.")->toResponse();
        }

        return $next($request);
    }
}
