<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillReminderRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'due_date' => ['required', 'date', 'after:today'],
            'reminder_days_before' => ['required', 'array'],
            'reminder_days_before.*' => ['integer', 'min:0'],
            'is_recurring' => ['boolean'],
            'frequency' => ['nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            'subscription_slug' => ['nullable', 'string', 'max:255'],
        ];
    }
}
