<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "entity" => $this->entity,
            "key" => $this->key,
            "name" => $this->name,
            "description" => $this->description,
            "color" => $this->color,
            "icon" => $this->icon,
            "is_initial" => $this->is_initial,
            "is_final" => $this->is_final,
            "sort_order" => $this->sort_order,
            "deleted_at" => $this->deleted_at,
        ];
    }
}
