<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'value' => $this->value,
            'installments' => $this->installments,
            'installment_value' => $this->installment_value,
            'discount' => $this->discount,
            'active' => $this->active,
        ];
    }
}
