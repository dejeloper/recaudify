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
        $user = auth('api')->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $now        = now();
        $dayOfWeek  = $now->dayOfWeek;
        $schedules  = $user->schedules()->where('day_of_week', $dayOfWeek)->get();

        // Sin horarios definidos → sin restricción
        if ($schedules->isEmpty()) {
            return $next($request);
        }

        $currentTime = $now->format('H:i');

        $allowed = $schedules->contains(
            fn ($s) => substr($s->start_time, 0, 5) <= $currentTime
                    && $currentTime <= substr($s->end_time, 0, 5)
        );

        if (! $allowed) {
            return ApiResult::forbidden('Acceso fuera del horario permitido.')->toResponse();
        }

        return $next($request);
    }
}
