<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'phone'              => $this->phone,
            'role'               => $this->role->value,
            'is_active'          => $this->is_active,
            'two_factor_enabled' => $this->two_factor_enabled,
            'created_at'         => $this->created_at,
        ];
    }
}
