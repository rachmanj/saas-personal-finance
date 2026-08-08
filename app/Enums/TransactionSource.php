<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Manual = 'manual';
    case Ocr = 'ocr';
    case Voice = 'voice';
    case Import = 'import';
    case Recurring = 'recurring';
    case Telegram = 'telegram';
}
