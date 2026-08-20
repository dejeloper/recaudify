<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "username" => $this->username,
            "email" => $this->email,
            "active" => $this->active,
            "branch_id" => $this->branch_id,
            "branch" => $this->whenLoaded("branch", fn() => new BranchResource($this->branch)),
            "roles" => $this->getRoleNames(),
            "permissions" => $this->getAllPermissions()->pluck("name"),
        ];
    }
}
