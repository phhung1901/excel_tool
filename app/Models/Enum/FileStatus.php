<?php
namespace App\Models\Enum;

enum FileStatus: int
{
    const UPLOADED = 0;
    const DOWNLOADED = 1;
    const DELETED = -1;
    const DATA_IMPORTING = 9;
    const DATA_IMPORTED = 10;
    const POS_RUNNING = 50;
    const POS_FINISHED = 51;
    const SERP_RUNNING = 70;
    const SERP_FINISHED = 71;
    const INTENT_RUNNING = 90;
    const INTENT_FINISHED = 100;

    public static function getKey($value)
    {
        return match ($value) {
            self::UPLOADED => 'UPLOADED',
            self::DOWNLOADED => 'DOWNLOADED',
            self::DELETED => 'DELETED',
            self::DATA_IMPORTING => 'DATA_IMPORTING',
            self::DATA_IMPORTED => 'DATA_IMPORTED',
            self::POS_RUNNING => 'POS_RUNNING',
            self::POS_FINISHED => 'POS_FINISHED',
            self::SERP_RUNNING => 'SERP_RUNNING',
            self::SERP_FINISHED => 'SERP_FINISHED',
            self::INTENT_RUNNING => 'INTENT_RUNNING',
            self::INTENT_FINISHED => 'INTENT_FINISHED',
        };
    }
}
