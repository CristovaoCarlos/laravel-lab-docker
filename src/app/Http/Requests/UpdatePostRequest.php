<?php

namespace App\Http\Requests;

use App\Contracts\SlugGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            // unique ignorando o próprio registro, senão o update falharia com o slug atual
            'slug' => ['sometimes', 'required', 'string', 'max:170', 'alpha_dash',
                Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'body' => ['sometimes', 'required', 'string', 'min:20'],
            'tags' => ['sometimes', 'array', 'max:5'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => app(SlugGenerator::class)->generate((string) $this->input('slug'))]);
        }
    }
}
