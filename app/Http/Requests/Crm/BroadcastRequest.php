<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BroadcastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'template_id'    => ['nullable', 'exists:broadcast_templates,id'],
            'message'        => ['nullable', 'string', 'max:2000'],
            'tier'           => ['nullable', Rule::in(['reguler', 'premium'])],
            'phone_hashes'   => ['nullable', 'array'],
            'phone_hashes.*' => ['string', 'size:64'],
        ];
    }
}
