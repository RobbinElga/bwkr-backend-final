<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'slug'               => $this->slug,
            'content'            => $this->content,
            'featured_image_url' => $this->featured_image_url,
            'author'             => $this->author,
            'category'           => $this->category,
            'tags'               => $this->tags ?? [],
            'meta_desc'          => $this->meta_desc,
            'status'             => $this->status->value,
            'likes_count'        => (int) $this->likes_count,
            'published_at'       => $this->published_at?->toIso8601String(),
            'created_at'         => $this->created_at,

        ];
    }
}
