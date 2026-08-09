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
        // Always return 200 immediately, then process async
        try {
            $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
            $expectedToken = config('services.telegram.webhook_secret');

            if (! hash_equals($expectedToken, $headerToken ?? '')) {
                return response()->noContent(200);
            }

            $update = $request->all();
            Log::info('Telegram webhook received', ['update_id' => $update['update_id'] ?? null]);

            $action = app(ProcessMessageAction::class);
            $reply = $action->handle($update);

            if (!empty($reply['chat_id']) && !empty($reply['text'])) {
                $bot = app(TelegramBotService::class);
                $bot->sendMessage($reply['chat_id'], $reply['text'], $reply['parse_mode'] ?? null);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
        }

        return response()->noContent(200);
    }
}
