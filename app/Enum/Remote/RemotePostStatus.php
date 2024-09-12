<?php

namespace App\Enum\Remote;

use App\Enum\EnumTrait;

enum RemotePostStatus: int
{
    use EnumTrait;
    case PUBLISH = 100;
    case DRAFT = 9;
    case EDITING = 99;
    case PENDING = 0;
    case DELETE = -1;
    case EDITED = 10;
}
