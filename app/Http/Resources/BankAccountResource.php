<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'bank_name'      => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name'   => $this->account_name,
            'logo_url'       => $this->logo_url,
            'qris_image_url' => $this->qris_image_url,
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at,
            'initial_balance' => $this->initial_balance,
        ];
    }
}
