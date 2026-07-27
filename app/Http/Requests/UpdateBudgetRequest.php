<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
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
        $budget = $this->route('budget');

        return [
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'period' => ['sometimes', 'required', 'in:monthly,yearly,custom'],
            'start_date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('budgets')->where(fn ($query) => $query
                    ->where('team_id', $this->user()->current_team_id)
                    ->where('category_id', $this->input('category_id', $budget->category_id)))
                    ->ignore($budget->id),
            ],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'rollover' => ['nullable', 'boolean'],
            'notification_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
