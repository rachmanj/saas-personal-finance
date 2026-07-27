<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
            'to_account_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('type') === 'transfer'),
                Rule::prohibitedIf(fn () => $this->input('type') && $this->input('type') !== 'transfer'),
                'exists:accounts,id',
                'different:account_id',
            ],
            'type' => ['sometimes', 'required', 'in:income,expense,transfer'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'transaction_date' => ['sometimes', 'required', 'date'],
            'receipt_path' => ['nullable', 'string'],
            'is_reconciled' => ['nullable', 'boolean'],
            'source' => ['nullable', 'in:manual,ocr,voice,import,recurring'],
        ];
    }
}
