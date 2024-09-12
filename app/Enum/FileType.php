<?php
namespace App\Enum;

use App\Enum\EnumTrait;

enum FileType: string
{
    use EnumTrait;

    case XLSX = 'xlsx';
    case CSV = 'csv';
    case TXT = 'txt';
}
