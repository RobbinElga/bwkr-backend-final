<?php

namespace App\Http\Requests\Testimonial;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'title'      => ['nullable', 'string', 'max:255'],
            'content'    => ['required', 'string'],
            'is_visible' => ['nullable', 'boolean'],
            'order'      => ['nullable', 'integer', 'min:0'],
            'photo'      => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
