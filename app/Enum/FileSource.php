<?php

namespace App\Enum;

use App\Enum\EnumTrait;

enum FileSource: string
{
    use EnumTrait;

    case AHREF = 'ahref';
    case SEMRUSH = 'semrush';
}
