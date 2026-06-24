<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'type'       => $this->type,
            'pic_name'   => $this->pic_name,
            'pic_phone'  => $this->pic_phone,
            'pic_email'  => $this->pic_email,
            'logo_url'   => $this->logo_url,
            'is_visible' => $this->is_visible,
            'created_at' => $this->created_at,
        ];
    }
}
