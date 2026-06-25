<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        Parameter::firstOrCreate(
            ['key' => 'shift-status'],
            ['value' => 'true', 'description' => 'Muestra el widget de estado de turno al usuario.'],
        );

        Parameter::firstOrCreate(
            ['key' => 'shift-status-countdown'],
            ['value' => 'true', 'description' => 'Muestra el contador regresivo de tiempo restante del turno.'],
        );

        Parameter::firstOrCreate(
            ['key' => 'geolocalization_login'],
            [
                'value' => 'true',
                'description' => 'Requiere permiso de geolocalización al iniciar sesión.',
            ],
        );
    }
}
