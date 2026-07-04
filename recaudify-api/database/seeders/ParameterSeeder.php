<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            // Authentication
            [
                "type" => "authentication",
                "key" => "shift_status_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Habilita la verificación de horario al iniciar sesión",
                "is_editable" => true,
            ],
            [
                "type" => "authentication",
                "key" => "shift_countdown_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Muestra el contador regresivo del turno activo",
                "is_editable" => true,
            ],
            [
                "type" => "authentication",
                "key" => "geolocalization_login_enabled",
                "value" => "true",
                "cast" => "boolean",
                "description" => "Solicita geolocalización al momento del login",
                "is_editable" => true,
            ],
        ];

        foreach ($parameters as $param) {
            Parameter::firstOrCreate(["type" => $param["type"], "key" => $param["key"]], $param);
        }
    }
}
