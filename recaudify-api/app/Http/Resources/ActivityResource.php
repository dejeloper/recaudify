<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    private const MODEL_LABELS = [
        "User" => "usuario",
        "UserSchedule" => "horario",
        "Role" => "rol",
        "Permission" => "permiso",
        "Parameter" => "parámetro",
    ];

    public function toArray(Request $request): array
    {
        $model = $this->subject_type ? class_basename($this->subject_type) : null;

        return [
            "id" => $this->id,
            "log_name" => $this->log_name,
            "event" => $this->event,
            "description" => $this->description,
            "model" => $model,
            "model_label" => $model ? self::MODEL_LABELS[$model] ?? strtolower($model) : null,
            "subject" => [
                "id" => $this->subject_id,
                "label" => $this->subject_label,
            ],
            "causer" => $this->buildCauser(),
            "changes" => $this->buildChanges(),
            "created_at" => $this->created_at,
        ];
    }

    /**
     * El nombre viene del snapshot congelado en el registro, no de la relación viva: si el usuario
     * se borró o se renombró, la historia sigue diciendo quién fue.
     */
    private function buildCauser(): ?array
    {
        if (!$this->causer_id && !$this->causer_username) {
            return null;
        }

        return [
            "id" => $this->causer_id,
            "name" => $this->causer_name ?? $this->causer?->name,
            "username" => $this->causer_username ?? $this->causer?->username,
            "exists" => $this->causer !== null,
        ];
    }

    private function buildChanges(): array
    {
        // El paquete guarda old/attributes dentro de properties; changes() los expone ya filtrados.
        $data = $this->resource->changes();
        $attributes = $data->get("attributes", []);
        $old = $data->get("old", []);

        $fields = array_keys($attributes ?: $old);
        $changes = [];

        foreach ($fields as $field) {
            $changes[] = [
                "field" => $field,
                "old" => $old[$field] ?? null,
                "new" => $attributes[$field] ?? null,
            ];
        }

        return $changes;
    }
}
