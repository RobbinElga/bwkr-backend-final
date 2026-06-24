<?php

namespace App\Http\Requests\Program;

use App\Enums\ProgramStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('program')?->id;

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('programs', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', Rule::enum(ProgramStatus::class)],
            'order'       => ['nullable', 'integer', 'min:0'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }
}
