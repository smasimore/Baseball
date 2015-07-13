<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class DateTimeUtils {

    public static function addDay($date) {
        return self::dsModify($date, '+1 day');
    }

    public static function subtractDate($date) {
        return self::dsModify($date, '-1 day');
    }

    public static function getESTDateTimeFromGMT($date, $time) {
        $hour = substr($time, 0, 2);
        $minute_second = split_string($time, $hour, AFTER, EXCL);
        // If the time is before 8am EST (12pm GMT) it's likely a night game on
        // the GMT day before. So 02 on 4/8 is likely a 4/7 night game.
        $date = $hour < 12 ? self::subtractDate($date) : $date;
        $hour -= 4;
        $hour = $hour < 0 ? 24 + $hour : $hour;
        $time = "$hour$minute_second";
        return array($date, $time);
    }

    public static function getSeasonFromDate($date) {
        $date = DateTime::createFromFormat('Y-m-d', $date);
        if (!$date) {
            throw new Exception('Date must be in Y-m-d format.');
        }
        return (int)$date->format('Y');
    }

    private static function dsModify($date, $day_change) {
        // Format of $day_change is '+1 day'
        $dateOneDayAdded = strtotime($date.$day_change);
        return date('Y-m-d', $dateOneDayAdded);
    }
}

?>
