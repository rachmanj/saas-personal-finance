<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringTransactionRequest extends FormRequest
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
            'account_id' => ['sometimes', 'required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['sometimes', 'required', 'in:income,expense,transfer'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'frequency' => ['sometimes', 'required', 'in:daily,weekly,monthly,yearly,custom'],
            'interval' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'template_type' => ['nullable', 'in:subscription,bill,salary,rent,custom'],
        ];
    }
}
