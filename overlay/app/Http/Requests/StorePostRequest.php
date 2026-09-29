<?php

namespace App\Http\Requests;

use App\Contracts\SlugGenerator;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    /** Retornar false gera resposta 403. */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Post::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:170', 'alpha_dash', Rule::unique('posts', 'slug')],
            'body' => ['required', 'string', 'min:20'],
            'tags' => ['sometimes', 'array', 'max:5'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título do post.',
        ];
    }

    public function attributes(): array
    {
        return [
            'published_at' => 'data de publicação',
        ];
    }

    /** Normaliza os dados antes de validar: gera o slug a partir do título, se não vier. */
    protected function prepareForValidation(): void
    {
        $source = $this->filled('slug') ? (string) $this->input('slug') : (string) $this->input('title', '');

        $this->merge(['slug' => app(SlugGenerator::class)->generate($source)]);
    }
}
