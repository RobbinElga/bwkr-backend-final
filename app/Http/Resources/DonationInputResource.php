<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DonationInputResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'ref_no'       => $this->ref_no,
            'donor_name'   => $this->donor_name,
            'salutation'   => $this->salutation,
            'donor_alias' => $this->donor_alias,
            'program_id'   => $this->program_id,
            'project_id'   => $this->project_id,
            'donor_phone'  => $this->donor_phone,   // didekripsi (hanya admin yang akses endpoint ini)
            'donor_email'  => $this->donor_email,
            'amount'       => $this->amount,
            'on_behalf'    => $this->on_behalf,
            'message'      => $this->message,
            'has_proof'    => $this->has_proof,
            'bank_account' => BankAccountResource::make($this->whenLoaded('bankAccount')),
            'source'       => $this->source->value,
            'status'         => $this->status->value,
            'donation_date'  => $this->donation_date?->format('Y-m-d'),
            'user_id'        => $this->user_id,
            'created_at'     => $this->created_at,
            'deleted_at'     => $this->deleted_at,
        ];
    }
}
