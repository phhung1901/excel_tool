<?php

namespace App\Enum;


use App\Enum\EnumTrait;

enum TaskStatus: int
{
    use EnumTrait;

    case ERROR = -1;
    case INIT = 0;
    case DONE = 1;
    case RUNNING = 100;
}
