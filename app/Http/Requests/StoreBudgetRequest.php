<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'period' => ['required', 'in:monthly,yearly,custom'],
            'start_date' => [
                'required',
                'date',
                Rule::unique('budgets')->where(fn ($query) => $query
                    ->where('team_id', $this->user()->current_team_id)
                    ->where('category_id', $this->input('category_id'))),
            ],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:period,custom'],
            'rollover' => ['nullable', 'boolean'],
            'notification_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
