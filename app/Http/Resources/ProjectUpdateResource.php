<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectUpdateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'project_id'   => $this->project_id,
            'title'        => $this->title,
            'content'      => $this->content,
            'image_urls'   => $this->image_urls,
            'published_at' => $this->published_at?->toIso8601String(),
            'order'        => $this->order,
        ];
    }
}
