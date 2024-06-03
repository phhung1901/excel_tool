<?php
namespace App\Models\Enum;

enum KeywordStatus: int
{
    const IMPORTED = 0;
    const SEARCH_PENDING = 9;
    const SEARCH_FAILED = -10;
    const SEARCH_SUCCESS = 10;
    const ORIGIN_KEYWORD = 1;
    const DUPLICATE_KEYWORD = -1;
}
