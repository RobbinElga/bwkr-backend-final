<?php

namespace App\Http\Requests\Achievement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count'  => ['required', 'integer', 'min:0'],
            'label'  => ['required', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:50'],
            'order'  => ['nullable', 'integer', 'min:0'],
            'image'  => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
