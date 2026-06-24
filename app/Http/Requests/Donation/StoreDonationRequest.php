<?php

namespace App\Http\Requests\Donation;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id'      => ['nullable', 'exists:programs,id'],
            'project_id'      => ['nullable', 'exists:projects,id'],
            'donor_name'      => ['required', 'string', 'max:255'],
            'salutation'      => ['nullable', 'string', 'in:Pak,Bu,Bang,Kak,Dek,Ustadz,Ustadzah,Mas,Mbak'],
            'donor_phone'     => ['required', 'string', 'max:20'],
            'donor_email'     => ['nullable', 'email', 'max:255'],
            'amount'          => ['required', 'integer', 'min:1000'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'on_behalf'       => ['nullable', 'string', 'max:255'],
            'message'         => ['nullable', 'string', 'max:1000'],
            'proof'           => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,pdf', 'max:10240'],
        ];
    }
}
