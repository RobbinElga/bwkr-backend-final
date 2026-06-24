<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'title'      => $this->title,
            'photo_url'  => $this->photo_url,
            'content'    => $this->content,
            'is_visible' => $this->is_visible,
            'order'      => $this->order,
            'created_at' => $this->created_at,
        ];
    }
}
