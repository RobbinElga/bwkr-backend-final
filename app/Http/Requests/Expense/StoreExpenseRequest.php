<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $threshold = (int) config('bwkr.expense.materai_threshold', 5_000_000);

        return [
            'project_id'      => ['required', 'exists:projects,id'],
            'amount'          => ['required', 'integer', 'min:1'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'receipt_file'    => ['required', 'file', 'mimes:jpeg,jpg,png,webp,heic,pdf', 'max:10240'],
            'ttd_file'        => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,heic,pdf', 'max:10240'],
            'materai_file'    => [
                Rule::requiredIf(fn() => (int) $this->input('amount') > $threshold),
                'file',
                'mimes:jpeg,jpg,png,webp,heic,pdf',
                'max:10240',
            ],
        ];
    }
}
