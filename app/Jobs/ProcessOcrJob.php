<?php

namespace App\Jobs;

use App\Enums\OcrJobStatus;
use App\Models\OcrJob;
use App\Services\OcrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessOcrJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public OcrJob $ocrJob)
    {
        $this->onQueue('receipts');
    }

    public function handle(OcrService $service): void
    {
        $this->ocrJob->update(['status' => OcrJobStatus::Processing]);

        try {
            $result = $service->parse($this->ocrJob->file_path);

            $this->ocrJob->update([
                'result' => $result,
                'status' => OcrJobStatus::Completed,
            ]);
        } catch (Throwable $e) {
            $this->ocrJob->update([
                'status' => OcrJobStatus::Failed,
                'error_log' => $e->getMessage(),
            ]);
        }
    }
}
