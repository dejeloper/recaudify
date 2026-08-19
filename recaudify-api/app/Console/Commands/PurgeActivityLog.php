<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Purga programada del log de auditoría.
 *
 * Comparte la misma implementación que el endpoint `POST /api/activities/purge`: hay una sola forma
 * de borrar auditoría en todo el sistema, y siempre queda registrada.
 */
class PurgeActivityLog extends Command
{
    protected $signature = "activity:purge
        {--days= : Días de retención. Por defecto usa el parámetro activity_log_retention_days}
        {--dry-run : Informa cuántos registros se eliminarían, sin borrar nada}";

    protected $description = "Elimina la actividad anterior al periodo de retención configurado";

    public function handle(ActivityService $activityService): int
    {
        $days = $this->option("days") !== null ? (int) $this->option("days") : null;

        if ($days !== null && $days < 1) {
            $this->error("El periodo de retención debe ser de al menos 1 día.");

            return self::FAILURE;
        }

        if ($this->option("dry-run")) {
            $preview = $activityService->previewPurge($days);

            $this->info(
                "Se eliminarían {$preview["deleted"]} registro(s) anteriores a {$preview["cutoff"]} " .
                    "(retención: {$preview["retention_days"]} días). No se borró nada.",
            );

            return self::SUCCESS;
        }

        $this->actAsSystemUser();

        $result = $activityService->purge($days);

        $this->info(
            "Eliminados {$result["deleted"]} registro(s) anteriores a {$result["cutoff"]} " .
                "(retención: {$result["retention_days"]} días).",
        );

        return self::SUCCESS;
    }

    /**
     * Firma la purga como el usuario de sistema.
     *
     * Sin esto la acción quedaría sin autor y en la auditoría aparecería como si no la hubiera hecho
     * nadie, que es justo lo que no queremos de un borrado.
     */
    private function actAsSystemUser(): void
    {
        $system = User::where("username", User::SYSTEM_USERNAME)->first();

        if (!$system) {
            $this->warn("No existe el usuario " . User::SYSTEM_USERNAME . ": la purga quedará registrada sin autor.");

            return;
        }

        Auth::guard("api")->setUser($system);
    }
}
