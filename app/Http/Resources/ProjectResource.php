<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BankAccountResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'program_id'       => $this->program_id,
            'program'          => ProgramResource::make($this->whenLoaded('program')),
            'name'             => $this->name,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'image_urls'       => $this->image_urls,
            'start_date'       => $this->start_date?->toDateString(),
            'end_date'         => $this->end_date?->toDateString(),
            'target_amount'    => $this->target_amount,
            'amount_raised'    => $this->amount_raised,
            'amount_spent'     => $this->amount_spent,
            'progress_percent' => $this->progress_percent,
            'shortfall'        => $this->shortfall,
            'remaining_funds'  => $this->remaining_funds,
            'updates'          => ProjectUpdateResource::collection($this->whenLoaded('updates')),
            'bank_accounts'    => BankAccountResource::collection($this->whenLoaded('bankAccounts')),
            'status'           => $this->status->value,
            'created_at'       => $this->created_at,
            'deleted_at'  => $this->deleted_at,
        ];
    }
}
