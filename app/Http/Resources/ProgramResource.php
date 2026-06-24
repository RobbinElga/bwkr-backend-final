<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'image_url'   => $this->image_url,
            'status'      => $this->status->value,
            'order'       => $this->order,
            'created_at'  => $this->created_at,
            'deleted_at'  => $this->deleted_at,
        ];
    }
}
