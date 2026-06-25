<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function getScheduleAccessError(User $user): ?string
    {
        if ($user->hasRole("superadmin")) {
            return null;
        }

        $schedules = $user->schedules()->get();

        if ($schedules->isEmpty()) {
            return "No tiene horario de acceso asignado.";
        }

        $now = now();
        $currentTime = $now->format("H:i");
        $allowed = $schedules
            ->where("day_of_week", $now->dayOfWeek)
            ->contains(
                fn($s) => substr($s->start_time, 0, 5) <= $currentTime && $currentTime <= substr($s->end_time, 0, 5),
            );

        return $allowed ? null : "Acceso fuera del horario permitido.";
    }

    public function getCurrentShift(User $user): array
    {
        if ($user->hasRole("superadmin")) {
            return [
                "is_within_schedule" => true,
                "show_status" => true,
                "day_of_week" => now()->dayOfWeek,
                "start_time" => null,
                "end_time" => null,
                "remaining_minutes" => null,
            ];
        }

        $now = now();
        $currentTime = $now->format("H:i");
        $dayOfWeek = $now->dayOfWeek;

        $currentSchedule = $user
            ->schedules()
            ->where("day_of_week", $dayOfWeek)
            ->get()
            ->first(
                fn($s) => substr($s->start_time, 0, 5) <= $currentTime && $currentTime <= substr($s->end_time, 0, 5),
            );

        return [
            "is_within_schedule" => $currentSchedule !== null,
            "show_status" => $currentSchedule ? (bool) $currentSchedule->show_status : false,
            "day_of_week" => $dayOfWeek,
            "start_time" => $currentSchedule ? substr($currentSchedule->start_time, 0, 5) : null,
            "end_time" => $currentSchedule ? substr($currentSchedule->end_time, 0, 5) : null,
            "remaining_minutes" => $currentSchedule
                ? (int) ((strtotime(substr($currentSchedule->end_time, 0, 5)) - strtotime($currentTime)) / 60)
                : null,
        ];
    }
}
