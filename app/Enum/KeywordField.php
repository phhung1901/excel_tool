<?php

namespace App\Enum;

enum KeywordField: string
{
    use EnumTrait;
    case MUSIC = "music";
    case BOOK = "book";
}
