<?php

namespace App\Jobs;

use App\Enums\VoiceJobStatus;
use App\Models\VoiceJob;
use App\Services\NlpTransactionParser;
use App\Services\VoiceTranscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessVoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public VoiceJob $voiceJob)
    {
        $this->onQueue('voice');
    }

    public function handle(VoiceTranscriptionService $transcriber, NlpTransactionParser $parser): void
    {
        $this->voiceJob->update(['status' => VoiceJobStatus::Processing]);

        try {
            $transcript = $transcriber->transcribe($this->voiceJob->audio_path);
            $result = $parser->parse($transcript);

            $this->voiceJob->update([
                'transcript' => $transcript,
                'result' => $result,
                'status' => VoiceJobStatus::Completed,
            ]);
        } catch (Throwable $e) {
            $this->voiceJob->update([
                'status' => VoiceJobStatus::Failed,
                'error_log' => $e->getMessage(),
            ]);
        }
    }
}
