<?php

namespace App\Http\Requests\ImpactVideo;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImpactVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'youtube_url' => ['sometimes', 'required', 'url', 'max:255'],
            'caption'     => ['nullable', 'string', 'max:255'],
            'program_id'  => ['nullable', 'exists:programs,id'],
            'project_id'  => ['nullable', 'exists:projects,id'],
            'order'       => ['nullable', 'integer', 'min:0'],
        ];
    }
}
