<?php

namespace App\Libs;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DateTimeHelper
{
    /**
     * @return array [start, end]
     */
    public static function parseDate(string $date)
    {
        $now = new Carbon();
        switch ($date) {
            case 'today':
                $end = $now->format('Y-m-d 23:59:59');
                $start = $now->format('Y-m-d 00:00:00');
                break;
            case 'yesterday':
                $now = new Carbon();
                $end = $now->clone()->sub(new \DateInterval('P1D'))->format('Y-m-d 23:59:59');
                $start = $now->clone()->sub(new \DateInterval('P1D'))->format('Y-m-d 00:00:00');
                break;
            case 'week':
                $now = new Carbon();
                $end = $now->format('Y-m-d 23:59:59');
                $start = $now->sub(new \DateInterval('P1W'))->format('Y-m-d 00:00:00');
                break;
            case 'month':
                $now = new Carbon();
                $end = $now->format('Y-m-d 23:59:59');
                $start = $now->sub(new \DateInterval('P1M'))->format('Y-m-d 00:00:00');
                break;
            case 'daily':
                $now = new Carbon();
                $end = $now->format('Y-m-d 23:59:59');
                $start = $now->sub(new \DateInterval('P1D'))->format('Y-m-d 00:00:00');
                break;
            default:
                $now = new Carbon($date);
                $end = $now->format('Y-m-d 23:59:59');
                $start = $now->format('Y-m-d 00:00:00');
                break;
        }

        return [$start, $end];
    }

    /**
     * @return array
     */
    public static function getDatesFromRange(string|Carbon $start, string|Carbon $end, string $format = 'Y-m-d')
    {
        $array = [];
        $start = $start instanceof Carbon ? $start : new Carbon($start);
        $end = $start instanceof Carbon ? $end : new Carbon($end);

        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $array[] = $date->format($format);
        }

        return $array;
    }
}
