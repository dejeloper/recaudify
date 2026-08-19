<?php

namespace Tests\Unit\Support;

use App\Enums\ParameterType;
use App\Models\Parameter;
use App\Support\ParameterSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ParameterScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function setParameter(string $value): void
    {
        Parameter::query()->updateOrCreate(
            ["type" => ParameterType::Application->value, "key" => "activity_log_purge_time"],
            ["value" => $value, "cast" => "string"],
        );

        Cache::flush();
    }

    public function test_returns_the_configured_time(): void
    {
        $this->setParameter("05:30");

        $this->assertEquals("05:30", ParameterSchedule::time("activity_log_purge_time"));
    }

    public function test_accepts_edge_times(): void
    {
        $this->setParameter("00:00");
        $this->assertEquals("00:00", ParameterSchedule::time("activity_log_purge_time"));

        $this->setParameter("23:59");
        $this->assertEquals("23:59", ParameterSchedule::time("activity_log_purge_time"));
    }

    public function test_falls_back_when_the_parameter_is_missing(): void
    {
        $this->assertEquals("03:00", ParameterSchedule::time("activity_log_purge_time"));
    }

    /**
     * Un valor inválido no puede tumbar la consola: `routes/console.php` se evalúa en cada comando
     * de artisan, así que un "25:99" guardado a mano rompería hasta `migrate`.
     */
    public function test_falls_back_when_the_value_is_invalid(): void
    {
        foreach (["25:99", "3:00", "0300", "madrugada", ""] as $invalid) {
            $this->setParameter($invalid);

            $this->assertEquals(
                "03:00",
                ParameterSchedule::time("activity_log_purge_time"),
                "El valor inválido '{$invalid}' debería caer al valor por defecto",
            );
        }
    }

    public function test_honours_a_custom_default(): void
    {
        $this->assertEquals("22:15", ParameterSchedule::time("parametro_inexistente", "22:15"));
    }
}
