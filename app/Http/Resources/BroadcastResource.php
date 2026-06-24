<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BroadcastResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'message'         => $this->message,
            'template_id'     => $this->template_id,
            'template_name'   => $this->whenLoaded('template', fn() => $this->template?->name),
            'tier'            => $this->tier,
            'status'          => $this->status->value,
            'recipient_count' => $this->recipient_count,
            'sent_at'         => $this->sent_at?->toIso8601String(),
            'created_at'      => $this->created_at,
        ];
    }
}
