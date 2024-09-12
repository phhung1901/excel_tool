<?php
namespace App\Enum;

use ReflectionClass;

enum KeywordStatus: int
{
    use EnumTrait;
    case IMPORTED = 0;
    case SEARCH_PENDING = 9;
    case SEARCH_FAILED = -10;
    case SEARCH_SUCCESS = 10;
    case ORIGIN_KEYWORD = 1;
    case DUPLICATE_KEYWORD = -1;
    case SEARCH_SPOTIFY_FAIL = -2;
    case GENERATE_PENDING = 99;
    case GENERATE_SUCCESS = 100;
    case GENERATE_FAILED = -100;

    public static function toArray(): array
    {
        $oClass = new ReflectionClass(__CLASS__);
        $array = [];
        foreach ($oClass->getConstants() as $key => $case) {
            $array[$case] = $key;
        }
        return $array;
    }
}
