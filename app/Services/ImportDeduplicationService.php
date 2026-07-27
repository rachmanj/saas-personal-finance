<?php

namespace App\Services;

use App\Models\Transaction;

class ImportDeduplicationService
{
    public function isDuplicate(int $accountId, string $date, float $amount, string $description): bool
    {
        $tolerance = 0.01;
        $normalizedAmount = abs($amount);

        $existing = Transaction::query()
            ->where('account_id', $accountId)
            ->where('transaction_date', $date)
            ->whereBetween('amount', [$normalizedAmount - $tolerance, $normalizedAmount + $tolerance])
            ->get();

        foreach ($existing as $txn) {
            if ($txn->description && $description) {
                similar_text(
                    strtolower(trim($txn->description)),
                    strtolower(trim($description)),
                    $percent
                );
                if ($percent > 75) {
                    return true;
                }
            }
        }

        return false;
    }
}
