<?php

namespace App\Ai\Enum;

enum DataTypeEnum: string
{
    case MARKDOWN = 'markdown';
    case HTML = 'html';
    case ARRAY = 'array';
    case TABLE = 'table';
    case IMAGES = 'images';
    case LINKS = 'links';
}
