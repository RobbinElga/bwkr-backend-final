<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('project')?->id;

        return [
            'program_id'    => ['sometimes', 'required', 'exists:programs,id'],
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('projects', 'slug')->ignore($id)],
            'description'   => ['nullable', 'string'],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_amount' => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', Rule::enum(ProjectStatus::class)],
            'images'        => ['nullable', 'array', 'max:10'],
            'images.*'      => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'bank_account_ids'   => ['nullable', 'array'],
            'bank_account_ids.*' => ['integer', 'exists:bank_accounts,id'],
        ];
    }
}
