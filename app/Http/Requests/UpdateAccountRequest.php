<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:checking,savings,credit_card,cash,investment'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'initial_balance' => ['nullable', 'numeric'],
            'include_in_net_worth' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];
    }
}
