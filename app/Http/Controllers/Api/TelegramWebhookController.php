<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // Validate secret token
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $expectedToken = config('services.telegram.webhook_secret');

        if (! hash_equals($expectedToken, $headerToken ?? '')) {
            return response()->noContent(403);
        }

        // Log the update for now (full routing in Phase 2)
        Log::info('Telegram webhook received', ['update' => $request->all()]);

        return response()->noContent(200);
    }
}
