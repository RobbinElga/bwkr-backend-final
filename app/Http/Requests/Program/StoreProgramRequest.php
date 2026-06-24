<?php

namespace App\Http\Requests\Program;

use App\Enums\ProgramStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:programs,slug'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', Rule::enum(ProgramStatus::class)],
            'order'       => ['nullable', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'], // 10MB
        ];
    }
}
