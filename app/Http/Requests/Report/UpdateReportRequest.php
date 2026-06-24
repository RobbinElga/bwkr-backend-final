<?php

namespace App\Http\Requests\Report;

use App\Enums\ReportCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('report')?->id;

        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'slug'         => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('reports', 'slug')->ignore($id)],
            'category'     => ['sometimes', Rule::enum(ReportCategory::class)],
            'year'         => ['nullable', 'integer', 'between:2000,2100'],
            'description'  => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'order'        => ['nullable', 'integer', 'min:0'],
            'cover'        => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'file'         => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
