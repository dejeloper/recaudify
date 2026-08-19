<?php

use App\Support\ParameterSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| En el servidor hace falta una sola entrada de cron para todas:
|
|   * * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
|
| Laravel decide cuáles corren en cada minuto. Para verlas: `php artisan schedule:list`.
*/

// Hora configurable desde Parámetros (activity_log_purge_time). Por defecto de madrugada, cuando
// nadie está trabajando: el borrado bloquea filas del log.
Schedule::command("activity:purge")
    ->dailyAt(ParameterSchedule::time("activity_log_purge_time"))
    ->skip(fn() => ParameterSchedule::maintenanceModeIsActive())
    ->withoutOverlapping()
    ->runInBackground();
