<?php

namespace App\Enum\Queue;

enum MonitorStatus: int
{
    case RUNNING = 0;
    case SUCCEEDED = 1;
    case FAILED = 2;
    case STALE = 3;
    case QUEUED = 4;

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

    public static function renderStatus($value)
    {
        switch ($value) {
            case self::RUNNING->value:
                return "<span class='px-3 rounded-5 py-1 bg-primary'>".self::search($value).'</span>';
            case self::SUCCEEDED->value:
                return "<span class='px-3 rounded-5 py-1 bg-success'>".self::search($value).'</span>';
            case self::FAILED->value:
                return "<span class='px-3 rounded-5 py-1 bg-danger'>".self::search($value).'</span>';
            case self::STALE->value:
                return "<span class='px-3 rounded-5 py-1 bg-dark'>".self::search($value).'</span>';
            case self::QUEUED->value:
                return "<span class='px-3 rounded-5 py-1 bg-warning'>".self::search($value).'</span>';
        }
    }
}
