<?php

namespace Database\Factories;

use App\Enums\OcrJobStatus;
use App\Models\OcrJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OcrJob>
 */
class OcrJobFactory extends Factory
{
    protected $model = OcrJob::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'file_path' => 'receipts/1/receipt.jpg',
            'status' => OcrJobStatus::Pending,
            'result' => null,
            'error_log' => null,
        ];
    }
}
