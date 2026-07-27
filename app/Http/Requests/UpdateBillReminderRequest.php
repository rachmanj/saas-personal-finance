<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillReminderRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'due_date' => ['sometimes', 'date', 'after:today'],
            'reminder_days_before' => ['sometimes', 'array'],
            'reminder_days_before.*' => ['integer', 'min:0'],
            'is_recurring' => ['sometimes', 'boolean'],
            'frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            'subscription_slug' => ['nullable', 'string', 'max:255'],
        ];
    }
}
