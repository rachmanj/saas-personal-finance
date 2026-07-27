<?php

namespace Database\Factories;

use App\Enums\VoiceJobStatus;
use App\Models\User;
use App\Models\VoiceJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoiceJob>
 */
class VoiceJobFactory extends Factory
{
    protected $model = VoiceJob::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'audio_path' => 'voice-notes/1/recording.webm',
            'transcript' => null,
            'status' => VoiceJobStatus::Pending,
            'result' => null,
            'error_log' => null,
        ];
    }
}
