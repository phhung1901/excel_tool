<?php

namespace App\Ai\Enum;

use App\Enum\EnumTrait;

enum AiService: string
{
    use EnumTrait;
    case NULL = 'null';
    case GEMINI = 'gemini';
    case CHAT_GPT = 'gpt';

}
