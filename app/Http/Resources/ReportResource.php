<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'category'     => $this->category->value,
            'year'         => $this->year,
            'description'  => $this->description,
            'cover_url'    => $this->cover_url,
            'file_url'     => $this->file_url,
            'is_published' => $this->is_published,
            'order'        => $this->order,
            'created_at'   => $this->created_at,
        ];
    }
}
