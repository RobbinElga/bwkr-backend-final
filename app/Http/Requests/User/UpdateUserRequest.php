<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'phone'     => ['sometimes', 'required', 'string', 'max:20'],
            'role'      => ['sometimes', Rule::in(['super_admin', 'admin', 'cs', 'fundraiser'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
