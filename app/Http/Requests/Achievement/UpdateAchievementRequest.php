<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count'  => ['sometimes', 'required', 'integer', 'min:0'],
            'label'  => ['sometimes', 'required', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:50'],
            'order'  => ['nullable', 'integer', 'min:0'],
            'image'  => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
