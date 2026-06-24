<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpactVideoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'youtube_url' => $this->youtube_url,
            'youtube_id' => $this->youtube_id,
            'caption'    => $this->caption,
            'program_id' => $this->program_id,
            'project_id' => $this->project_id,
            'order'      => $this->order,
        ];
    }
}
