<?php

namespace App\Services;

class OfxImportParser
{
    public function parse(string $filePath): array
    {
        $content = file_get_contents($filePath);

        $accountInfo = [
            'account_id' => $this->extractTag($content, 'ACCTID'),
            'bank_id' => $this->extractTag($content, 'BANKID'),
            'account_type' => $this->extractTag($content, 'ACCTTYPE'),
        ];

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $matches);

        $transactions = [];

        foreach ($matches[1] as $block) {
            $type = $this->extractTag($block, 'TRNTYPE');
            $dateRaw = $this->extractTag($block, 'DTPOSTED');
            $amountRaw = $this->extractTag($block, 'TRNAMT');
            $name = $this->extractTag($block, 'NAME');
            $memo = $this->extractTag($block, 'MEMO');

            $amount = (float) $amountRaw;

            if ($type === 'DEBIT' && $amount > 0) {
                $amount = -$amount;
            } elseif ($type === 'CREDIT' && $amount < 0) {
                $amount = abs($amount);
            }

            $transactions[] = [
                'type' => $type,
                'date' => $this->formatDate($dateRaw),
                'amount' => $amount,
                'description' => $name,
                'memo' => $memo,
            ];
        }

        return [
            'account_info' => $accountInfo,
            'transactions' => $transactions,
            'total_rows' => count($transactions),
        ];
    }

    private function extractTag(string $content, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'>([^<\r\n]+)/', $content, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function formatDate(?string $dateRaw): ?string
    {
        if ($dateRaw === null || strlen($dateRaw) < 8) {
            return null;
        }

        return substr($dateRaw, 0, 4).'-'.substr($dateRaw, 4, 2).'-'.substr($dateRaw, 6, 2);
    }
}
