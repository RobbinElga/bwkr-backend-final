<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['nullable', 'string', 'max:255'],
            'pic_name'   => ['nullable', 'string', 'max:255'],
            'pic_phone'  => ['nullable', 'string', 'max:20'],
            'pic_email'  => ['nullable', 'email', 'max:255'],
            'is_visible' => ['nullable', 'boolean'],
            'logo' => [
                'nullable',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (! in_array($ext, ['jpeg', 'jpg', 'png', 'webp', 'svg'], true)) {
                        $fail('Logo harus JPG, PNG, WEBP, atau SVG.');
                    }
                },
            ],

        ];
    }
}
