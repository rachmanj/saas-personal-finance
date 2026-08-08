<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $bot): Response
    {
        // Validate secret token
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $expectedToken = config('services.telegram.webhook_secret');

        if (! hash_equals($expectedToken, $headerToken ?? '')) {
            return response()->noContent(403);
        }

        $update = $request->all();
        Log::info('Telegram webhook received', ['update' => $update]);

        // Minimal auto-reply (Phase 2 will replace with full NLP parsing)
        $message = $update['message'] ?? null;
        if ($message && isset($message['chat']['id']) && isset($message['text'])) {
            $chatId = $message['chat']['id'];
            $text = $message['text'];

            if (str_starts_with($text, '/start')) {
                $reply = "👋 Halo! Aku *Ngopi Dulu Donk* — asisten catatan keuangan kamu.\n\n"
                    . "Ketik apa yang kamu beli, contoh:\n"
                    . "• \"makan siang 50rb\"\n"
                    . "• \"gaji 5jt\"\n\n"
                    . "Fitur lengkap segera hadir! 🚀";
                $bot->sendMessage($chatId, $reply, 'Markdown');
            } else {
                $bot->sendMessage($chatId, "📝 Pesan diterima: \"{$text}\"\n\n*Fitur input transaksi sedang dikembangkan.*\nNanti kamu bisa langsung catat transaksi lewat chat ini! 🛠️", 'Markdown');
            }
        }

        return response()->noContent(200);
    }
}
