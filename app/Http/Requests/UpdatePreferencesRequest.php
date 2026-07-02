<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sources' => ['array'],
            'sources.*' => ['integer', 'exists:sources,id'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'authors' => ['array'],
            'authors.*' => ['integer', 'exists:authors,id'],
        ];
    }
}
