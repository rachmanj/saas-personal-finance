<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Services\CurrencyConverterService;
use Illuminate\Support\Facades\DB;

class CreateTransactionAction
{
    public function __construct(private CurrencyConverterService $converter) {}

    public function execute(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $baseCurrency = $data['base_currency'] ?? 'IDR';
            $rate = $this->converter->rateFor($data['currency'], $baseCurrency, $data['transaction_date'] ?? null);

            $transaction = Transaction::create([
                ...$data,
                'base_currency' => $baseCurrency,
                'base_amount' => round($data['amount'] * $rate, 2),
                'exchange_rate' => $rate,
            ]);

            if (! empty($data['splits'])) {
                $transaction->splits()->createMany($data['splits']);
            }

            if (! empty($data['tag_ids'])) {
                $transaction->tags()->sync($data['tag_ids']);
            }

            $this->applyBalanceChange($transaction);

            return $transaction->load(['splits', 'tags', 'category', 'account']);
        });
    }

    private function applyBalanceChange(Transaction $transaction): void
    {
        if ($transaction->type->value === 'income') {
            $transaction->account()->increment('balance', $transaction->base_amount);
        } elseif ($transaction->type->value === 'expense') {
            $transaction->account()->decrement('balance', $transaction->base_amount);
        } elseif ($transaction->type->value === 'transfer' && $transaction->to_account_id) {
            $transaction->account()->decrement('balance', $transaction->base_amount);
            $transaction->toAccount()->increment('balance', $transaction->base_amount);
        }
    }
}
