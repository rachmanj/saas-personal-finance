<?php

namespace App\Enums;

enum ImportFileType: string
{
    case Csv = 'csv';
    case Ofx = 'ofx';
}
