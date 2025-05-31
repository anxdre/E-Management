<?php

namespace App;

use Illuminate\Support\Carbon;

trait DateHelper
{
    function diffTimeFormatted(Carbon $start, Carbon $end): string {
        $totalSeconds = $start->diffInSeconds($end,true);
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    function diffTimeMinusFormatted(Carbon $start, Carbon $end): string {
        $totalSeconds = $start->diffInSeconds($end);

        $sign = $totalSeconds < 0 ? '-' : '';
        $totalSeconds = abs($totalSeconds);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%s%02d:%02d:%02d', $sign, $hours, $minutes, $seconds);
    }

    function formatTime($time)
    {
        return $time ? Carbon::parse($time)->format('H:i:s') : null;
    }
}
