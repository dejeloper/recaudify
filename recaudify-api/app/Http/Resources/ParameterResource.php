<?php

namespace App\Http\Resources;

use App\Services\ParameterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(ParameterService::class);

        return [
            "id" => $this->id,
            "type" => $this->type->value,
            "type_label" => $this->type->label(),
            "key" => $this->key,
            "value" => $this->value,
            "typed_value" => $this->value !== null ? $service->resolveValue($this->value, $this->cast) : null,
            "cast" => $this->cast->value,
            "description" => $this->description,
            "is_editable" => $this->is_editable,
            "updated_at" => $this->updated_at,
        ];
    }
}
