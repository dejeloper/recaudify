<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'user' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'status' => $this->status, // success | failed
            'reason' => $this->reason, // invalid_credentials | inactive | out_of_schedule | null
            'ip_address' => $this->ip_address,
            'os' => $this->os_name ? ['name' => $this->os_name, 'version' => $this->os_version] : null,
            'device_type' => $this->device_type,
            'geolocation' => $this->latitude !== null
                    ? [
                        'latitude' => $this->latitude,
                        'longitude' => $this->longitude,
                        'accuracy' => $this->accuracy,
                    ]
                    : null,
            'logged_at' => $this->logged_at,
        ];
    }
}
