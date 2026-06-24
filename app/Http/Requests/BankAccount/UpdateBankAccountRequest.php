<?php

namespace App\Http\Requests\BankAccount;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'           => ['sometimes', 'in:bank,qris'],
            'bank_name'      => ['sometimes', 'required', 'string', 'max:100'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'account_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'      => ['nullable', 'boolean'],
            'logo'           => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'qris_image'     => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'initial_balance' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
