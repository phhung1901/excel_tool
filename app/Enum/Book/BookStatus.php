<?php

namespace App\Enum\Book;

use App\Enum\EnumTrait;

enum BookStatus: int
{
    use EnumTrait;
    case INIT = 0;
    case IGNORED = -1;

}
