<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // bisa kode TOTP 6 digit ATAU backup code (mis. "AB3CD-EF9GH")
            'code' => ['required', 'string', 'max:20'],
        ];
    }
}
