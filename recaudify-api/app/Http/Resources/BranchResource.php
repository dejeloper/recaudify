<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "code" => $this->code,
            "name" => $this->name,
            "address" => $this->address,
            "city" => $this->city,
            "phone" => $this->phone,
            "email" => $this->email,
            "is_main" => $this->is_main,
            "sort_order" => $this->sort_order,
            "users_count" => $this->whenCounted("users"),
            "deleted_at" => $this->deleted_at,
        ];
    }
}
