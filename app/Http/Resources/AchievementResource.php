<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'image_url' => $this->image_url,
            'count'     => $this->count,
            'label'     => $this->label,
            'period'    => $this->period,
            'order'     => $this->order,
        ];
    }
}
