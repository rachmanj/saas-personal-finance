<?php

namespace App\Actions\Telegram;

use App\Actions\Budgets\CalculateBudgetUtilizationAction;
use App\Actions\Transactions\CreateTransactionAction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Services\CategorizationRuleService;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessMessageAction
{
    /**
     * Process an incoming Telegram update and return a reply payload.
     *
     * @return array{chat_id: string, text: string, parse_mode?: string}
     */
    public function handle(array $update): array
    {
        $message = $update['message'] ?? null;

        if (!$message) {
            return $this->reply('', 'Invalid update');
        }

        $chat = $message['chat'] ?? [];
        $from = $message['from'] ?? [];
        $chatId = (string) ($chat['id'] ?? '0');
        $text = $message['text'] ?? '';

        // Find or create TelegramUser
        $telegramUser = $this->findOrCreateTelegramUser($chat, $from);

        // Handle commands
        if (!empty($text) && str_starts_with($text, '/start')) {
            return $this->handleStartCommand($chatId, $telegramUser, $from);
        }

        if (!empty($text) && str_starts_with($text, '/help')) {
            return $this->handleHelpCommand($chatId);
        }

        if (!empty($text) && str_starts_with($text, '/balance')) {
            return $this->handleBalanceCommand($chatId, $telegramUser);
        }

        if (!empty($text) && str_starts_with($text, '/today')) {
            return $this->handleTodayCommand($chatId, $telegramUser);
        }

        if (!empty($text) && str_starts_with($text, '/month')) {
            return $this->handleMonthCommand($chatId, $telegramUser);
        }

        if (!empty($text) && str_starts_with($text, '/budget')) {
            return $this->handleBudgetCommand($chatId, $telegramUser);
        }

        if (!empty($text) && str_starts_with($text, '/categories')) {
            return $this->handleCategoriesCommand($chatId, $telegramUser);
        }

        if (!empty($text) && str_starts_with($text, '/delete')) {
            return $this->handleDeleteCommand($chatId, $telegramUser, $text);
        }

        if (!empty($text) && str_starts_with($text, '/kategori')) {
            return $this->handleKategoriCommand($chatId, $telegramUser, $text);
        }

        // Handle photo/voice messages
        if (isset($message['photo']) || isset($message['voice'])) {
            $messageType = isset($message['photo']) ? 'photo' : 'voice';
            $fileId = isset($message['photo'])
                ? end($message['photo'])['file_id'] ?? null
                : ($message['voice']['file_id'] ?? null);

            $this->saveTelegramMessage($telegramUser, 'inbound', $messageType, '', $fileId, 'processed');

            // Download and OCR the file
            try {
                $bot = app(\App\Services\TelegramBotService::class);
                $fileInfo = $bot->getFile($fileId);
                if ($fileInfo && isset($fileInfo['file_path'])) {
                    $dir = $messageType === 'photo' ? 'telegram/photos' : 'telegram/voice';
                    $savePath = storage_path("app/{$dir}/{$fileId}.jpg");
                    @mkdir(dirname($savePath), 0777, true);
                    $bot->downloadFile($fileInfo['file_path'], $savePath);

                    $ocrService = app(\App\Services\OcrService::class);
                    $ocrResult = $ocrService->parse($savePath);

                    if (!empty($ocrResult['raw_text'])) {
                        // Try to parse OCR text as transaction
                        $parser = new \App\Actions\Telegram\ParseTransactionTextAction;
                        $parsed = $parser->execute($ocrResult['raw_text']);

                        if ($parsed['amount'] !== null) {
                            $account = $this->findAccount($telegramUser);
                            if ($account) {
                                $transaction = $this->createTransaction($telegramUser, $account, $parsed);
                                $reply = $this->formatTransactionReply($transaction, $parsed, $ocrResult['merchant'], $ocrResult['raw_text']);
                                $merchant = $ocrResult['merchant'] ? " di <b>{$ocrResult['merchant']}</b>" : '';
                                return $this->reply($chatId, "📸 <b>Struk diproses!</b>{$merchant}\n\n" . $reply);
                            }
                        }

                        // OCR worked but no amount found — show what was extracted
                        $preview = substr($ocrResult['raw_text'], 0, 200);
                        return $this->reply($chatId, "📸 <b>Teks terdeteksi:</b>\n<pre>{$preview}</pre>\n\nKirim totalnya: <b>[jumlah]</b>");
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Photo processing failed: ' . $e->getMessage());
            }

            // Fallback
            $reply = "📸 <b>Struk diterima!</b>\n\nSilakan ketik detail transaksi:\n<b>[nama toko] [total]</b>\n\nContoh: <code>Toko Barokah 9000</code>";
            return $this->reply($chatId, $reply);
        }

        // Handle text messages — parse and create transaction
        if (!empty($text) && !str_starts_with($text, '/')) {
            return $this->handleTextMessage($chatId, $telegramUser, $text, $message);
        }

        // Fallback
        return $this->reply($chatId, 'Pesan tidak dikenali. Ketik /help untuk bantuan.');
    }

    /**
     * Handle a text message: parse, create transaction, reply.
     */
    private function handleTextMessage(string $chatId, TelegramUser $telegramUser, string $text, array $message): array
    {
        // Save inbound message
        $this->saveTelegramMessage($telegramUser, 'inbound', 'text', $text, null, 'pending');

        // Parse the text
        $parser = new ParseTransactionTextAction;
        $parsed = $parser->execute($text);

        // No amount found
        if ($parsed['amount'] === null) {
            // Update message status
            $this->updateLastMessage($telegramUser, 'failed', 'No amount detected');

            $reply = "Maaf, aku nggak bisa menemukan jumlah uang di pesanmu. 😅\n\n"
                . "Coba format seperti ini ya:\n"
                . "• <b>makan siang 50rb</b>\n"
                . "• <b>gaji 5jt</b>\n"
                . "• <b>bensin 100k</b>\n\n"
                . "Ketik /help untuk panduan lengkap.";
            return $this->reply($chatId, $reply);
        }

        // Find account for the linked user
        $account = $this->findAccount($telegramUser);

        if (!$account) {
            $this->updateLastMessage($telegramUser, 'failed', 'No account found');

            if (!$telegramUser->user_id) {
                $reply = "Kamu belum menghubungkan akun Telegram ke akun Ngopi Dulu Donk.\n\n"
                    . "Silakan buka aplikasi web dan hubungkan Telegram kamu dari menu Pengaturan. 📱";
                return $this->reply($chatId, $reply);
            }

            $reply = "Tidak ada rekening aktif ditemukan. Silakan buat rekening dulu di aplikasi web. 🏦";
            return $this->reply($chatId, $reply);
        }

        // Create the transaction
        try {
            $transaction = $this->createTransaction($telegramUser, $account, $parsed);
        } catch (\Throwable $e) {
            Log::error('Telegram: failed to create transaction', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            $this->updateLastMessage($telegramUser, 'failed', $e->getMessage());

            $reply = "⚠️ Gagal mencatat transaksi. Coba lagi ya.";
            return $this->reply($chatId, $reply);
        }

        // Update message with transaction_id and status
        $this->updateLastMessageWithTransaction($telegramUser, $transaction->id);

        // Format reply
        $reply = $this->formatTransactionReply($transaction, $parsed);

        return $this->reply($chatId, $reply);
    }

    /**
     * Handle the /start command.
     */
    private function handleStartCommand(string $chatId, TelegramUser $telegramUser, array $from): array
    {
        $this->saveTelegramMessage($telegramUser, 'inbound', 'command', '/start', null, 'processed');

        $firstName = htmlspecialchars($from['first_name'] ?? 'Sobat');

        if (!$telegramUser->user_id) {
            $reply = "👋 Halo <b>{$firstName}</b>! Aku <b>Ngopi Dulu Donk</b> — asisten catatan keuangan kamu.\n\n"
                . "Sebelum mulai catat transaksi, kamu perlu menghubungkan Telegram ke akun web dulu ya.\n\n"
                . "🔗 <b>Cara menghubungkan:</b>\n"
                . "1. Buka aplikasi web Ngopi Dulu Donk\n"
                . "2. Masuk ke <b>Pengaturan</b> → <b>Telegram</b>\n"
                . "3. Klik <b>Hubungkan</b> dan ikuti petunjuknya\n\n"
                . "Setelah terhubung, kamu bisa langsung catat transaksi lewat chat ini!\n\n"
                . "Ketik /help untuk panduan lengkap. 🚀";
        } else {
            $reply = "👋 Halo lagi <b>{$firstName}</b>!\n\n"
                . "Akun kamu sudah terhubung. 🎉\n\n"
                . "Langsung catat transaksi aja, contoh:\n"
                . "• <b>makan siang 50rb</b>\n"
                . "• <b>gaji 5jt</b>\n"
                . "• <b>bensin 100k</b>\n\n"
                . "Ketik /help untuk panduan lengkap!";
        }

        return $this->reply($chatId, $reply);
    }

    /**
     * Handle the /help command.
     */
    private function handleHelpCommand(string $chatId): array
    {
        $reply = "📖 <b>Ngopi Dulu Donk — Panduan</b>\n\n"
            . "<b>Catat Pengeluaran:</b>\n"
            . "• <code>makan siang 50rb</code>\n"
            . "• <code>beli bensin 100k</code>\n"
            . "• <code>bayar listrik 500rb</code>\n\n"
            . "<b>Catat Pemasukan:</b>\n"
            . "• <code>gaji 5jt</code>\n"
            . "• <code>bonus 1.5jt</code>\n"
            . "• <code>jual barang 200rb</code>\n\n"
            . "<b>Format jumlah:</b>\n"
            . "• <code>50rb</code> / <code>50ribu</code> = 50.000\n"
            . "• <code>1.5jt</code> / <code>1,5juta</code> = 1.500.000\n"
            . "• <code>100k</code> = 100.000\n"
            . "• <code>50000</code> = 50.000\n\n"
            . "<b>Perintah:</b>\n"
            . "/start — Mulai bot\n"
            . "/help — Tampilkan panduan ini\n"
            . "/balance — Lihat saldo rekening 💰\n"
            . "/today — Transaksi hari ini 📅\n"
            . "/month — Ringkasan bulan ini 📊\n"
            . "/budget — Status anggaran 💳\n"
            . "/categories — Daftar kategori 📂";

        return $this->reply($chatId, $reply);
    }

    /**
     * Get the team ID for a linked Telegram user, or return an unlinked prompt.
     */
    private function getLinkedTeamId(TelegramUser $telegramUser, string $chatId): ?int
    {
        if (!$telegramUser->user_id) {
            return null;
        }

        return $telegramUser->user->current_team_id ?? null;
    }

    /**
     * Return the unlinked-account prompt response.
     */
    private function unlinkedPrompt(string $chatId): array
    {
        return $this->reply($chatId,
            "⚠️ Kamu belum menghubungkan Telegram ke akun Ngopi Dulu Donk.\n\n"
            . "Silakan buka aplikasi web dan hubungkan Telegram dari menu <b>Pengaturan</b>. 📱"
        );
    }

    /**
     * Handle the /balance command — show all active accounts with balances.
     */
    private function handleBalanceCommand(string $chatId, TelegramUser $telegramUser): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $accounts = Account::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($accounts->isEmpty()) {
            return $this->reply($chatId,
                "💰 <b>Saldo Rekening</b>\n\n"
                . "Belum ada rekening aktif. Silakan buat rekening dulu di aplikasi web. 🏦"
            );
        }

        $lines = ["💰 <b>Saldo Rekening</b>\n"];

        $total = 0;
        foreach ($accounts as $account) {
            $balance = (float) $account->balance;
            $total += $balance;
            $formatted = number_format($balance, 0, ',', '.');
            $icon = match ($account->type) {
                'savings' => '🏦',
                'credit_card' => '💳',
                'investment' => '📈',
                default => '💵',
            };
            $lines[] = "{$icon} <b>{$account->name}</b>: Rp {$formatted}";
        }

        $formattedTotal = number_format($total, 0, ',', '.');
        $lines[] = "\n💎 <b>Total:</b> Rp {$formattedTotal}";

        return $this->reply($chatId, implode("\n", $lines));
    }

    /**
     * Handle the /today command — show today's transactions with totals.
     */
    private function handleTodayCommand(string $chatId, TelegramUser $telegramUser): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $today = Carbon::today()->toDateString();

        $transactions = Transaction::query()
            ->where('team_id', $teamId)
            ->where('transaction_date', $today)
            ->orderByDesc('id')
            ->get();

        if ($transactions->isEmpty()) {
            return $this->reply($chatId,
                "📅 <b>Transaksi Hari Ini</b>\n\n"
                . "Belum ada transaksi hari ini. Yuk catat pengeluaranmu! ✍️"
            );
        }

        // Calculate totals
        $incomeTotal = (float) $transactions->where('type', 'income')->sum('amount');
        $expenseTotal = (float) $transactions->where('type', 'expense')->sum('amount');

        $lines = ["📅 <b>Transaksi Hari Ini</b> — " . Carbon::today()->format('d M Y') . "\n"];

        foreach ($transactions as $tx) {
            $emoji = $tx->type->value === 'income' ? '💰' : '💸';
            $amount = number_format((float) $tx->amount, 0, ',', '.');
            $lines[] = "{$emoji} {$tx->description} — <b>Rp {$amount}</b>";
        }

        $lines[] = "\n━━━━━━━━━━━━━━━";
        $lines[] = "💰 Pemasukan: <b>Rp " . number_format($incomeTotal, 0, ',', '.') . "</b>";
        $lines[] = "💸 Pengeluaran: <b>Rp " . number_format($expenseTotal, 0, ',', '.') . "</b>";

        $net = $incomeTotal - $expenseTotal;
        $netEmoji = $net >= 0 ? '🟢' : '🔴';
        $lines[] = "{$netEmoji} Net: <b>Rp " . number_format($net, 0, ',', '.') . "</b>";

        return $this->reply($chatId, implode("\n", $lines));
    }

    /**
     * Handle the /month command — show current month summary.
     */
    private function handleMonthCommand(string $chatId, TelegramUser $telegramUser): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
        $now = Carbon::now();

        $incomeTotal = (float) Transaction::query()
            ->where('team_id', $teamId)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $expenseTotal = (float) Transaction::query()
            ->where('team_id', $teamId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        if ($incomeTotal === 0.0 && $expenseTotal === 0.0) {
            return $this->reply($chatId,
                "📊 <b>Ringkasan Bulan Ini</b> — " . $now->format('M Y') . "\n\n"
                . "Belum ada transaksi bulan ini. Saatnya catat keuanganmu! 💪"
            );
        }

        $net = $incomeTotal - $expenseTotal;
        $netEmoji = $net >= 0 ? '🟢' : '🔴';

        $lines = ["📊 <b>Ringkasan Bulan Ini</b> — " . $now->format('M Y') . "\n"];
        $lines[] = "💰 Pemasukan: <b>Rp " . number_format($incomeTotal, 0, ',', '.') . "</b>";
        $lines[] = "💸 Pengeluaran: <b>Rp " . number_format($expenseTotal, 0, ',', '.') . "</b>";
        $lines[] = "{$netEmoji} Net: <b>Rp " . number_format($net, 0, ',', '.') . "</b>";

        return $this->reply($chatId, implode("\n", $lines));
    }

    /**
     * Handle the /budget command — show budget utilization for active budgets.
     */
    private function handleBudgetCommand(string $chatId, TelegramUser $telegramUser): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $budgets = Budget::query()
            ->where('team_id', $teamId)
            ->with('category')
            ->get();

        if ($budgets->isEmpty()) {
            return $this->reply($chatId,
                "💳 <b>Status Anggaran</b>\n\n"
                . "Belum ada anggaran. Buat anggaran di aplikasi web untuk mulai tracking! 🎯"
            );
        }

        $utilizationAction = new CalculateBudgetUtilizationAction;

        $lines = ["💳 <b>Status Anggaran</b>\n"];

        foreach ($budgets as $budget) {
            $utilization = $utilizationAction->execute($budget);
            $statusEmoji = match ($utilization['status']) {
                'over' => '🔴',
                'warning' => '🟡',
                default => '🟢',
            };

            $categoryName = $budget->category?->name ?? 'Tanpa Kategori';
            $spent = number_format($utilization['spent'], 0, ',', '.');
            $budgetAmount = number_format((float) $budget->amount, 0, ',', '.');
            $percent = $utilization['percent'];

            $lines[] = "{$statusEmoji} <b>{$categoryName}</b>";
            $lines[] = "   Rp {$spent} / Rp {$budgetAmount} ({$percent}%)";
        }

        return $this->reply($chatId, implode("\n", $lines));
    }

    /**
     * Handle the /categories command — list available categories.
     */
    private function handleCategoriesCommand(string $chatId, TelegramUser $telegramUser): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $incomeCategories = Category::query()
            ->where('team_id', $teamId)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $expenseCategories = Category::query()
            ->where('team_id', $teamId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($incomeCategories->isEmpty() && $expenseCategories->isEmpty()) {
            return $this->reply($chatId,
                "📂 <b>Daftar Kategori</b>\n\n"
                . "Belum ada kategori. Kategori akan dibuat otomatis saat pertama kali kamu buka aplikasi web. 🏷️"
            );
        }

        $lines = ["📂 <b>Daftar Kategori</b>\n"];

        if ($incomeCategories->isNotEmpty()) {
            $lines[] = "<b>💰 Pemasukan:</b>";
            foreach ($incomeCategories as $cat) {
                $icon = $cat->icon ? "{$cat->icon} " : '';
                $lines[] = "  {$icon}{$cat->name}";
            }
        }

        if ($expenseCategories->isNotEmpty()) {
            $lines[] = "\n<b>💸 Pengeluaran:</b>";
            foreach ($expenseCategories as $cat) {
                $icon = $cat->icon ? "{$cat->icon} " : '';
                $lines[] = "  {$icon}{$cat->name}";
            }
        }

        return $this->reply($chatId, implode("\n", $lines));
    }

    /**
     * Handle the /delete command — delete a transaction by ID.
     * Format: /delete [id]
     */
    private function handleDeleteCommand(string $chatId, TelegramUser $telegramUser, string $text): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);

        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        // Extract the transaction ID from the command text
        $parts = explode(' ', trim($text), 2);
        $searchValue = $parts[1] ?? '';

        if (empty($searchValue)) {
            return $this->reply($chatId, 'Transaksi tidak ditemukan');
        }

        // Search by ID (numeric) or by kode_transaksi (string)
        $transaction = null;
        if (is_numeric($searchValue)) {
            $transaction = Transaction::where('team_id', $teamId)
                ->where('id', (int) $searchValue)
                ->first();
        } else {
            // Fallback: search by description containing the code
            $transaction = Transaction::where('team_id', $teamId)
                ->where('description', 'like', "%{$searchValue}%")
                ->first();
        }

        if (!$transaction) {
            return $this->reply($chatId, 'Transaksi tidak ditemukan');
        }

        $transactionId = $transaction->id;
        $transaction->delete();

        // Save outbound message
        $this->saveTelegramMessage($telegramUser, 'outbound', 'command', "/delete {$transactionId}", null, 'processed');

        return $this->reply($chatId, "🗑️ Transaksi #{$transactionId} dihapus");
    }

    /**
     * Handle /kategori command.
     * /kategori → list categories
     * /kategori [ID] [name] → change category
     */
    private function handleKategoriCommand(string $chatId, TelegramUser $telegramUser, string $text): array
    {
        $teamId = $this->getLinkedTeamId($telegramUser, $chatId);
        if ($teamId === null) {
            return $this->unlinkedPrompt($chatId);
        }

        $parts = explode(' ', trim($text), 3);
        $txnId = $parts[1] ?? '';
        $catName = $parts[2] ?? '';

        if (empty($txnId)) {
            $categories = Category::where('team_id', $teamId)
                ->where('is_active', true)->orderBy('type')->orderBy('name')->get();
            if ($categories->isEmpty()) {
                return $this->reply($chatId, 'Tidak ada kategori.');
            }
            $list = "📂 <b>Kategori:</b>\n";
            foreach ($categories as $c) {
                $icon = $c->type === 'income' ? '💰' : '💸';
                $list .= "{$icon} {$c->name}\n";
            }
            $list .= "\n<code>/kategori [ID] [kategori]</code>";
            return $this->reply($chatId, $list);
        }

        if (empty($catName)) {
            return $this->reply($chatId, "Format: <code>/kategori [ID] [nama_kategori]</code>");
        }

        $transaction = Transaction::where('team_id', $teamId)
            ->where('id', (int) $txnId)->first();
        if (! $transaction) {
            return $this->reply($chatId, 'Transaksi tidak ditemukan.');
        }

        $category = Category::where('team_id', $teamId)
            ->where('name', 'like', "%{$catName}%")
            ->where('type', $transaction->type->value)->first();
        if (! $category) {
            return $this->reply($chatId, "Kategori \"{$catName}\" tidak ditemukan.");
        }

        $transaction->update(['category_id' => $category->id]);
        return $this->reply($chatId, "✅ #{$txnId} → <b>{$category->name}</b>");
    }

    /**
     * Find or create a TelegramUser from chat/from data.
     */
    private function findOrCreateTelegramUser(array $chat, array $from): TelegramUser
    {
        $chatId = $chat['id'] ?? 0;

        $telegramUser = TelegramUser::where('chat_id', $chatId)->first();

        if (!$telegramUser) {
            $telegramUser = TelegramUser::create([
                'user_id' => null,
                'chat_id' => $chatId,
                'username' => $from['username'] ?? null,
                'first_name' => $from['first_name'] ?? 'Unknown',
                'last_name' => $from['last_name'] ?? null,
                'language_code' => $from['language_code'] ?? null,
                'is_active' => true,
            ]);
        }

        return $telegramUser;
    }

    /**
     * Find the active account for the Telegram user's team.
     */
    private function findAccount(TelegramUser $telegramUser): ?Account
    {
        if (!$telegramUser->user_id) {
            return null;
        }

        $teamId = $telegramUser->user->current_team_id ?? null;

        if (!$teamId) {
            return null;
        }

        return Account::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * Create a transaction from parsed data.
     */
    private function createTransaction(TelegramUser $telegramUser, Account $account, array $parsed): \App\Models\Transaction
    {
        $createAction = new CreateTransactionAction(new CurrencyConverterService);

        $data = [
            'team_id' => $telegramUser->user->current_team_id,
            'user_id' => $telegramUser->user_id,
            'account_id' => $account->id,
            'type' => $parsed['type'],
            'amount' => $parsed['amount'],
            'currency' => $account->currency,
            'description' => $parsed['description'],
            'transaction_date' => now()->toDateString(),
            'source' => 'telegram',
        ];

        $transaction = $createAction->execute($data);

        // Auto-categorize
        $this->autoCategorize($transaction, $parsed['description']);

        return $transaction;
    }

    /**
     * Auto-categorize the transaction using CategorizationRuleService.
     */
    private function autoCategorize(\App\Models\Transaction $transaction, string $description): void
    {
        try {
            $ruleService = app(CategorizationRuleService::class);
            $suggestion = $ruleService->suggest($description);

            if ($suggestion['category_id']) {
                $transaction->update(['category_id' => $suggestion['category_id']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram: auto-categorize failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Save a TelegramMessage record.
     */
    private function saveTelegramMessage(TelegramUser $telegramUser, string $direction, string $type, string $content, ?string $fileId = null, string $status = 'pending'): TelegramMessage
    {
        return TelegramMessage::create([
            'telegram_user_id' => $telegramUser->id,
            'direction' => $direction,
            'message_type' => $type,
            'content' => $content,
            'file_id' => $fileId,
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    /**
     * Update the last inbound message's status.
     */
    private function updateLastMessage(TelegramUser $telegramUser, string $status, ?string $error = null): void
    {
        $lastMessage = TelegramMessage::where('telegram_user_id', $telegramUser->id)
            ->where('direction', 'inbound')
            ->latest('created_at')
            ->first();

        if ($lastMessage) {
            $lastMessage->update([
                'status' => $status,
                'error' => $error,
            ]);
        }
    }

    /**
     * Update the last inbound message with transaction ID.
     */
    private function updateLastMessageWithTransaction(TelegramUser $telegramUser, int $transactionId): void
    {
        $lastMessage = TelegramMessage::where('telegram_user_id', $telegramUser->id)
            ->where('direction', 'inbound')
            ->latest('created_at')
            ->first();

        if ($lastMessage) {
            $lastMessage->update([
                'status' => 'processed',
                'transaction_id' => $transactionId,
            ]);
        }
    }

    /**
     * Format a transaction confirmation reply.
     */
    private function formatTransactionReply(\App\Models\Transaction $transaction, array $parsed, ?string $ocrMerchant = null, ?string $ocrItems = null): string
        {
            $emoji = $transaction->type->value === 'income' ? '💰' : '💸';
            $label = $transaction->type->value === 'income' ? 'Pemasukan' : 'Pengeluaran';
            $amount = number_format(abs($transaction->amount), 0, ',', '.');

            $reply = "✅ <b>Transaksi berhasil dicatat!</b>\n\n";

            $reply .= "🆔 <b>ID:</b> {$transaction->id}\n";

            // Store/Source
            $source = $ocrMerchant ?? $transaction->description;
            $reply .= "🏪 <b>Toko/Sumber:</b> {$source}\n";

            // Items (from OCR)
            if ($ocrItems) {
                $reply .= "📦 <b>Items:</b> {$ocrItems}\n";
            }

            $reply .= "💵 <b>Total:</b> Rp {$amount}\n";
            $reply .= "📅 <b>Tanggal:</b> {$transaction->transaction_date->format('Y-m-d')}\n";

            if ($transaction->category) {
                $reply .= "📂 <b>Kategori:</b> {$transaction->category->name}\n";
            }

            return $reply;
        }

    /**
     * Build a reply payload.
     */
    private function reply(string $chatId, string $text): array
    {
        return [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];
    }
}
