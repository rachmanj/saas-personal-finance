<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';
    protected $description = 'Register the Telegram bot webhook URL';

    public function handle(TelegramBotService $botService): int
    {
        $this->info('Setting Telegram webhook...');

        $result = $botService->setWebhook();

        if ($result['ok'] ?? false) {
            $this->info('✅ Webhook set successfully:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->error('❌ Failed to set webhook:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::FAILURE;
    }
}
