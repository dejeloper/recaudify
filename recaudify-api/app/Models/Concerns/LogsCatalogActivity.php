<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Auditoría legible para catálogos: registra create/update/delete/restore
 * con descripción en español, en el log "catalogos", guardando solo los
 * campos que cambian. Cada modelo declara sus campos en activitylogFields().
 */
trait LogsCatalogActivity
{
    use LogsActivity;

    abstract protected function activitylogFields(): array;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName("catalogos")
            ->logOnly($this->activitylogFields())
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(
                fn(string $event) => match ($event) {
                    "created" => "creó",
                    "updated" => "actualizó",
                    "deleted" => "eliminó",
                    "restored" => "restauró",
                    default => $event,
                },
            );
    }
}
