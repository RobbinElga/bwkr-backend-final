<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'project_id'    => $this->project_id,
            'project'       => ProjectResource::make($this->whenLoaded('project')),
            'amount'        => $this->amount,
            'needs_materai' => $this->needs_materai,
            'has_receipt'   => ! empty($this->attributes['receipt_file']),
            'has_ttd'       => ! empty($this->attributes['ttd_file']),
            'has_materai'   => ! empty($this->attributes['materai_file']),
            'bank_account'  => BankAccountResource::make($this->whenLoaded('bankAccount')),
            'status'        => $this->status->value,
            'notes'         => $this->notes,
            'approved_at'   => $this->approved_at?->toIso8601String(),
            'created_at'    => $this->created_at,
        ];
    }
}
