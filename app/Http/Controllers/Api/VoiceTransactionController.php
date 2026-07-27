<?php

namespace App\Http\Controllers\Api;

use App\Enums\VoiceJobStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessVoiceJob;
use App\Models\VoiceJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceTransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,webm,ogg|max:20480',
        ]);

        $path = $request->file('audio')->store(
            'voice-notes/'.$request->user()->current_team_id,
            'voice_notes'
        );

        $voiceJob = VoiceJob::create([
            'user_id' => $request->user()->id,
            'audio_path' => $path,
            'status' => VoiceJobStatus::Pending,
        ]);

        ProcessVoiceJob::dispatch($voiceJob);

        return response()->json([
            'data' => ['id' => $voiceJob->id, 'status' => $voiceJob->status->value],
            'message' => 'Voice note uploaded, processing started',
            'errors' => null,
            'meta' => null,
        ], 201);
    }

    public function status(VoiceJob $voiceJob): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $voiceJob->id,
                'status' => $voiceJob->status->value,
                'transcript' => $voiceJob->transcript,
                'result' => $voiceJob->result,
                'error_log' => $voiceJob->error_log,
            ],
            'message' => 'Voice job status retrieved',
            'errors' => null,
            'meta' => null,
        ]);
    }
}
