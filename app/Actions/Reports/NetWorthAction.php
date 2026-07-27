<?php

namespace App\Actions\Reports;

use App\Models\Account;

class NetWorthAction
{
    /**
     * Calculate net worth from accounts marked include_in_net_worth.
     *
     * @return array{total_net_worth: string, accounts: array}
     */
    public function execute(int $teamId): array
    {
        $accounts = Account::query()
            ->where('team_id', $teamId)
            ->where('include_in_net_worth', true)
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => number_format((float) $account->balance, 2, '.', ''),
                'currency' => $account->currency,
            ]);

        $total = $accounts->sum(fn ($a) => (float) $a['balance']);

        return [
            'total_net_worth' => number_format($total, 2, '.', ''),
            'accounts' => $accounts->toArray(),
        ];
    }
}