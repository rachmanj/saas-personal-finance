<?php

namespace App\Services;

class VoiceTranscriptionService
{
    public function transcribe(string $audioPath): string
    {
        $transcripts = [
            'Makan siang 50 ribu hari ini',
            'Bayar listrik 500 ribu',
            'Gaji masuk 10 juta',
            'Beli bensin 200 ribu di Shell',
            'Nonton bioskop 75 ribu',
        ];

        return $transcripts[array_rand($transcripts)];
    }
}
