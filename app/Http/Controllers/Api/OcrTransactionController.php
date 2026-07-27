<?php

namespace App\Http\Controllers\Api;

use App\Enums\OcrJobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOcrJob;
use App\Models\OcrJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OcrTransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['receipt' => 'required|image|max:10240']);

        $path = $request->file('receipt')->store(
            'receipts/'.$request->user()->current_team_id,
            'receipts'
        );

        $ocrJob = OcrJob::create([
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'status' => OcrJobStatus::Pending,
        ]);

        ProcessOcrJob::dispatch($ocrJob);

        return response()->json([
            'data' => ['id' => $ocrJob->id, 'status' => $ocrJob->status->value],
            'message' => 'Receipt uploaded, processing started',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function status(OcrJob $ocrJob): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $ocrJob->id,
                'status' => $ocrJob->status->value,
                'result' => $ocrJob->result,
                'error_log' => $ocrJob->error_log,
            ],
            'message' => 'OCR job status retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
