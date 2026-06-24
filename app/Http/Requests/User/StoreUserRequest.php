<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'     => ['required', 'string', 'max:20'],
            'role'      => ['required', Rule::in(['super_admin', 'admin', 'cs', 'fundraiser'])], // bukan donatur
            'password'  => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
