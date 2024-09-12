<?php
namespace App\Enum;

enum FileStatus: int
{
    use EnumTrait;

    case FAIL = -10;
    case UPLOADED = 0;
    case DELETED = -1;
    case DOWNLOADED = 1;
    case IMPORTING = 9;
    case IMPORTED = 10;
    case POS_RUNNING = 50;
    case POS_FINISHED = 51;
    case SERP_RUNNING = 70;
    case SERP_FINISHED = 71;
    case INTENT_RUNNING = 90;
    case INTENT_FINISHED = 100;

    public function type() {
        return match ($this) {
            default => 'secondary',
            self::IMPORTED => 'primary',
        };
    }
}
