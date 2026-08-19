<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsModelActivity
{
    use LogsActivity;

    abstract protected function activitylogFields(): array;

    abstract protected function logName(): string;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->logName())
            ->logOnly($this->activitylogFields())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
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
