<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonaturDonationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'ref_no'       => $this->ref_no,
            'donor_name'   => $this->donor_name,
            'amount'       => $this->amount,
            'on_behalf'    => $this->on_behalf,
            'message'      => $this->message,
            'status'       => $this->status->value,
            'source'         => $this->source->value,
            'donation_date'  => $this->donation_date?->format('Y-m-d'),
            'project'      => $this->whenLoaded('project', fn() => $this->project ? [
                'name' => $this->project->name,
                'slug' => $this->project->slug,
            ] : null),
            'program'      => $this->whenLoaded('program', fn() => $this->program ? [
                'name' => $this->program->name,
            ] : null),
            'bank_account' => BankAccountResource::make($this->whenLoaded('bankAccount')),
            'created_at'   => $this->created_at,
        ];
    }
}
