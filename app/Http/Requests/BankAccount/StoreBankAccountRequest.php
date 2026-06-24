<?php

namespace App\Http\Requests\BankAccount;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'           => ['required', 'in:bank,qris'],
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required_if:type,bank', 'nullable', 'string', 'max:50'],
            'account_name'   => ['required_if:type,bank', 'nullable', 'string', 'max:255'],
            'is_active'      => ['nullable', 'boolean'],
            'logo'           => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'qris_image'     => ['required_if:type,qris', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'initial_balance' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
