<?php

namespace App\Enum;

enum ActivityLogName: string
{
    case DEFAULT = 'model';
    case CTV_EDIT_DOCUMENT = 'ctv_edit_document';
    case CTV_EDIT_OLD_DOCUMENT = 'ctv_edit_old_document';

    public static function toArray()
    {
        $results = [];
        foreach (self::cases() as $item) {
            $results[$item->name] = $item->value;
        }

        return $results;
    }

    public static function search($value)
    {
        $array = self::toArray();

        return array_search($value, $array);
    }
}
