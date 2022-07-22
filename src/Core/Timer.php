<?php

namespace Zeero\Core;

use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * Timer class
 * 
 * simple to work with dates
 * 
 * @author Carlos Bumba
 */

final class Timer
{


    /**
     * Get a DateTime from string
     *
     * @param string $str
     * @param DateTimeZone|null $timezone
     * @param string|null $format
     * @return void
     */
    public static function fromStr(string $str, DateTimeZone $timezone = null, string $format = null)
    {
        $date = new DateTime($str, $timezone);
        return $format ? $date->format($format) : $date;
    }


    /**
     * Get the current Y-m-d Date
     *
     * @return string
     */
    public static function today(): string
    {
        return date("Y-m-d");
    }


    /**
     * get the current date or time in format specified or Y-m-d H:i:s by default
     *
     * @param string $format
     * @return string
     */
    public static function now(string $format = null): string
    {
        return date($format ?? "Y-m-d H:i:s");
    }


    /**
     * get the current timestamp
     *
     * @return int
     */
    public static function curTimestamp()
    {
        return date_timestamp_get(new DateTime());
    }


    /**
     * Get the date of tomorrow
     *
     * @return string
     */
    public static function tomorrow(): string
    {
        $d = new DateTime;
        $i = new DateInterval("P1D");
        $d->add($i);
        return $d->format("Y-m-d");
    }

    /**
     * Get the Yersterday date
     *
     * @return string
     */
    public static function yersterday(): string
    {
        $d = new DateTime;
        $i = new DateInterval("P1D");
        $d->sub($i);
        return $d->format("Y-m-d");
    }


    /**
     * Get the Date of the next #days
     *
     * @param int $days the next days number
     * @param string $date the start date
     * @return string
     */
    public static function next($days, $date = null): string
    {
        $d = new DateTime($date ?? "now");
        $i = new DateInterval("P{$days}D");
        $d->add($i);
        return $d->format("Y-m-d");
    }



    /**
     * Get the Date of the previous #days
     *
     * @param int $days the previous days number
     * @param string $date the start date
     * @return string
     */
    public static function prev($days, $date = null): string
    {
        $d = new DateTime($date ?? "now");
        $i = new DateInterval("P{$days}D");
        $d->sub($i);
        return $d->format("Y-m-d");
    }


    /**
     * Get The Date Interval
     *
     * @param string $d1 the first date
     * @param string $d2 the second date
     * @return DateInterval
     */
    public static function dif($d1, $d2 = null): DateInterval
    {
        $d1 = new DateTime($d1);
        $d2 = new DateTime($d2 ?? "now");
        return $d1->diff($d2);
    }
}
