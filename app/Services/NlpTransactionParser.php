<?php

namespace App\Services;

class NlpTransactionParser
{
    public function parse(string $transcript): array
    {
        $result = [
            'amount' => null,
            'merchant' => null,
            'type' => 'expense',
            'category' => null,
            'notes' => $transcript,
        ];

        if (preg_match('/(\d+)\s*(ribu)/i', $transcript, $m)) {
            $result['amount'] = (int) $m[1] * 1000;
        } elseif (preg_match('/(\d+)\s*(juta)/i', $transcript, $m)) {
            $result['amount'] = (int) $m[1] * 1000000;
        } elseif (preg_match('/(\d[\d,.]*)\s*(rb|k)/i', $transcript, $m)) {
            $result['amount'] = (int) str_replace([',', '.'], '', $m[1]) * 1000;
        } elseif (preg_match('/(\d[\d,.]*)/', $transcript, $m)) {
            $result['amount'] = (int) str_replace([',', '.'], '', $m[1]);
        }

        if (preg_match('/gaji|masuk|dapat|terima|bonus/i', $transcript)) {
            $result['type'] = 'income';
        }

        if (preg_match('/^(.*?)(\d)/', $transcript, $m)) {
            $result['merchant'] = trim($m[1]);
        }

        return $result;
    }
}
