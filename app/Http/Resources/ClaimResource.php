<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'donation_input_id' => $this->donation_input_id,
            'project_id'        => $this->project_id,
            'project'           => ProjectResource::make($this->whenLoaded('project')),
            'donation'          => DonationInputResource::make($this->whenLoaded('donationInput')),
            'amount'            => $this->amount,
            'notes'             => $this->notes,
            'status'            => $this->status->value,
            'approved_at'       => $this->approved_at?->toIso8601String(),
            'created_at'        => $this->created_at,
        ];
    }
}
