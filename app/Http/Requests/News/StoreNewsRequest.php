<?php

namespace App\Http\Requests\News;

use App\Enums\NewsStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:news,slug'],
            'content'        => ['nullable', 'string'],
            'author'         => ['nullable', 'string', 'max:255'],
            'category'       => ['nullable', 'string', 'max:100'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string', 'max:50'],
            'meta_desc'      => ['nullable', 'string', 'max:500'],
            'status'         => ['nullable', Rule::enum(NewsStatus::class)],
            'published_at'   => ['nullable', 'date'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
