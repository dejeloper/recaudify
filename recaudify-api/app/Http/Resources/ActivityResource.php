<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Mapa de clase de modelo → etiqueta legible en singular.
     */
    private const MODEL_LABELS = [
        "Product" => "producto",
        "Rate" => "tarifa",
        "Seller" => "vendedor",
        "CallReason" => "motivo de llamada",
    ];

    public function toArray(Request $request): array
    {
        $model = $this->subject_type ? class_basename($this->subject_type) : null;

        return [
            "id" => $this->id,
            "log_name" => $this->log_name,
            "event" => $this->event,
            "description" => $this->description, // verbo en español: creó/actualizó/...
            "model" => $model, // ej. "Product"
            "model_label" => $model ? self::MODEL_LABELS[$model] ?? strtolower($model) : null,
            "subject" => [
                "id" => $this->subject_id,
                // label resuelto en el ActivityService (soporta registros eliminados)
                "label" => $this->subject_label,
            ],
            "causer" => $this->causer ? ["id" => $this->causer->id, "name" => $this->causer->name] : null,
            "changes" => $this->buildChanges(),
            "created_at" => $this->created_at,
        ];
    }

    /**
     * Normaliza los cambios a una lista [{ field, old, new }] lista para pintar.
     * - created/restored: solo "new".
     * - updated: "old" y "new" (solo campos que cambiaron).
     * - deleted: solo "old".
     */
    private function buildChanges(): array
    {
        $data = $this->attribute_changes ?? collect();
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
