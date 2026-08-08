<?php

namespace App\Http\Controllers\Api;

use App\Actions\Telegram\ProcessMessageAction;
use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
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

        $update = $request->all();
        Log::info('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

        try {
            $action = app(ProcessMessageAction::class);
            $reply = $action->handle($update);

            // Send reply back to Telegram
            if (!empty($reply['chat_id']) && !empty($reply['text'])) {
                $bot = app(TelegramBotService::class);
                $bot->sendMessage(
                    $reply['chat_id'],
                    $reply['text'],
                    $reply['parse_mode'] ?? null
                );
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook failed', [
                'error' => $e->getMessage(),
                'update' => $update,
            ]);
        }

        return response()->noContent(200);
    }
}
