<?php

namespace Database\Factories;

use App\Enums\ImportFileType;
use App\Enums\ImportStatus;
use App\Models\Account;
use App\Models\Import;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        $fileType = fake()->randomElement(ImportFileType::cases());

        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'file_path' => 'imports/'.fake()->uuid().'.'.$fileType->value,
            'file_type' => $fileType,
            'status' => ImportStatus::Pending,
            'total_rows' => fake()->numberBetween(5, 50),
            'imported_rows' => 0,
            'skipped_rows' => 0,
            'error_log' => null,
            'column_mapping' => $fileType === ImportFileType::Csv ? [
                'date' => 'Date',
                'description' => 'Description',
                'amount' => 'Amount',
            ] : null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_rows'] ?? 10;

            return [
                'status' => ImportStatus::Completed,
                'imported_rows' => $total,
                'skipped_rows' => 0,
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ImportStatus::Failed,
            'error_log' => ['message' => 'Import failed'],
        ]);
    }
}
