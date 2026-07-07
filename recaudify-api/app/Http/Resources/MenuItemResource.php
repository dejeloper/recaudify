<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "parent_id" => $this->parent_id,
            "label" => $this->label,
            "icons" => $this->icons,
            "route" => $this->route,
            "permission" => $this->permission,
            "order" => $this->order,
            "is_active" => $this->is_active,
            "children" => $this->whenLoaded("children", fn() => MenuItemResource::collection($this->children)),
        ];
    }
}
