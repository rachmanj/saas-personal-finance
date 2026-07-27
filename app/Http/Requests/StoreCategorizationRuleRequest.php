<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorizationRuleRequest extends FormRequest
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
            'pattern' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
