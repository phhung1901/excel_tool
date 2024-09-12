<?php

namespace App\Libs\System\DB;

use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class CrDB
{
    protected const FORMAT_TZ = 'Y-m-d H:i:sP';

    protected static function parse(string|Carbon $date, $timezone = null): Carbon
    {
        return $date instanceof Carbon ?
            ($timezone ? $date->setTimezone($timezone) : $date) : Carbon::parse($date, $timezone);
    }

    public static function timestampTz(string|Carbon $date, $timezone = null): Expression
    {
        if ($date instanceof Carbon) {
            $date = $date->setTimezone($timezone)->format(self::FORMAT_TZ);
        }

        return \DB::raw("timestamptz '".$date."'");
    }

    public static function startOfDayTz(string|Carbon|null $day = null, $timezone = null): Expression
    {
        $start_today = self::parse($day ?: now(), $timezone)->startOfDay()->format('Y-m-d H:i:sP');

        return self::timestampTz($start_today);
    }

    public static function endOfDayTz(string|Carbon|null $day = null, $timezone = null): Expression
    {
        $start_today = self::parse($day ?: now(), $timezone)->endOfDay()->format('Y-m-d H:i:sP');

        return self::timestampTz($start_today);
    }

    public static function rangeDaysTz(string|Carbon $date_start, string|Carbon|null $date_end = null, $timezone = null): array
    {
        $date_start = self::parse($date_start, $timezone);
        $start = self::timestampTz($date_start->clone()->startOfDay()->format(self::FORMAT_TZ));
        if ($date_end) {
            $date_end = self::parse($date_end, $timezone);
            $end = self::timestampTz($date_end->endOfDay()->format(self::FORMAT_TZ));
        } else {
            $end = self::timestampTz($date_start->endOfDay()->format(self::FORMAT_TZ));
        }

        return [$start, $end];
    }

    public static function dateTrunc(string $field, $timezone_abb = -7)
    {
        return DB::raw("DATE_TRUNC('day', timezone('$timezone_abb', $field)) as date");
    }
}
