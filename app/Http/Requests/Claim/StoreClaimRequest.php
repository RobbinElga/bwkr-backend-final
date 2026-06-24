<?php

namespace App\Http\Requests\Claim;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donation_input_id' => ['required', 'exists:donations_input,id'],
            'project_id'        => ['required', 'exists:projects,id'],
            'amount'            => ['required', 'integer', 'min:1000'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }
}
