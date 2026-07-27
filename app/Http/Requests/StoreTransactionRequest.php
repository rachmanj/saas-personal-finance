<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'to_account_id' => [
                Rule::requiredIf(fn () => $this->input('type') === 'transfer'),
                Rule::prohibitedIf(fn () => $this->input('type') !== 'transfer'),
                'exists:accounts,id',
                'different:account_id',
            ],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'transaction_date' => ['required', 'date'],
            'receipt_path' => ['nullable', 'string'],
            'is_reconciled' => ['nullable', 'boolean'],
            'source' => ['nullable', 'in:manual,ocr,voice,import,recurring'],
            'splits' => ['nullable', 'array'],
            'splits.*.category_id' => ['required_with:splits', 'exists:categories,id'],
            'splits.*.amount' => ['required_with:splits', 'numeric', 'min:0.01'],
            'splits.*.description' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['exists:tags,id'],
        ];
    }
}
