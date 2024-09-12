<?php

namespace App\Libs\System\DB;

class CockroachArray
{
    public static function fromArray(...$arrays)
    {
        return array_values(array_unique(array_merge(...$arrays)));
    }

    public static function fromDB(string $string): array
    {
        $raw = str_replace(['{', '}', ','], ['["', '"]', '","'], $string);

        return json_decode($raw, true) ?: [];
    }

    public static function toSql(array $array): string
    {
        if (empty($array)) {
            return '{}';
        }

        return str_replace(['["', '"]', '","'], ['{', '}', ','], json_encode($array));
    }
}
