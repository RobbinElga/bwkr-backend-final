<?php

namespace App\Http\Requests\ProjectUpdate;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'order'        => ['nullable', 'integer', 'min:0'],
            'images'       => ['nullable', 'array', 'max:10'],
            'images.*'     => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
