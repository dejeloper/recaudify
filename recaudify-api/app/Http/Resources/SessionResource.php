<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user" => $this->when(
                $this->relationLoaded("user") && $this->user,
                fn() => ["id" => $this->user->id, "name" => $this->user->name],
            ),
            "ip_address" => $this->ip_address,
            "os" => $this->os_name ? ["name" => $this->os_name, "version" => $this->os_version] : null,
            "device_type" => $this->device_type,
            "last_used_at" => $this->last_used_at,
            "created_at" => $this->created_at,
            "expires_at" => $this->expires_at,
            "is_current" => $this->when(isset($this->is_current), fn() => (bool) $this->is_current),
        ];
    }
}
