<?php
namespace DTApi\Helpers;

use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use DTApi\Models\Holiday;
use Illuminate\Support\Facades\Log;

class DateTimeHelper
{

    /*Funciton to get the next business time.*/
    public static function getNextBusinessTimeString()
    {
        $time_zone_str = 'Etc/GMT-1';
        $date = new DateTime("now", new DateTimeZone($time_zone_str));
        $hh = $date->format('H');
        $next_business_date = $date->format('Y-m-d');
        if ($hh >= 20) {
            $date = $date->format('Y-m-d');
            $next_business_date = date('Y-m-d', strtotime($date . ' +1 day'));
        }
        $next_business_date .= ' 07:00:00 GMT+0100';        // sample date format - 2015-09-24 14:00:00 GMT-0700
        return $next_business_date;
    }

    /*Function to check is the if there is night outsite or not*/
    public static function isNightTime()
    {
        $time_zone_str = 'Etc/GMT-1';
        $date = new DateTime("now", new DateTimeZone($time_zone_str));
        $hh = $date->format('H');
        if ($hh >= 20 || $hh < 7) {    // non-business time
            return true;
        }
        return false;
    }

    /*
     * return diff value of ($to_time - $from_time) by minutes
     */
    public static function getTimeDiff($to_time, $from_time)
    {
        $time_diff = $to_time - $from_time;
        return intval($time_diff / 60);
    }

    /**
     * Method to calculate if session was at inconvenience hours. Catch start date and session_duration in format Y-m-d H:i:s and in hours. Return how long was IH. Inconvenience hours are: whole weekends or from 6 pm till 7 am workdays.
     * @param $start_date
     * @param $session_duration
     * @param $inconvenience_settings
     * @param $holidays
     * @return mixed
     */
    public static function calculateInconvenienceHours($start_date, $session_duration, $inconvenience_settings = null, $holidays = null)
    {

        $weekdays_start = 18;
        $weekdays_end = 7;
        $weekend_start = 0;
        $weekend_end = 0;
        $holiday_start = 0;
        $holiday_end = 0;
        if (!is_null($inconvenience_settings)) {
            $weekdays_start = $inconvenience_settings['weekdays_start'];
            $weekdays_end = $inconvenience_settings['weekdays_end'];
            if ($inconvenience_settings->weekends_day_before_after == 'yes') {
                $weekend_start = $inconvenience_settings['weekend_start'];
                $weekend_end = $inconvenience_settings['weekend_end'];
            }
            if ($inconvenience_settings->holiday_day_before_after == 'yes') {
                $holiday_start = $inconvenience_settings['holiday_start'];
                $holiday_end = $inconvenience_settings['holiday_end'];
            }
        }

        $start_time_in_inconvenience = false;
        $end_time_in_inconvenience = false;
        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $start_date);
        $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $start_date);
        $end_date->addMinutes($session_duration * 60);

        if ($start_date->isWeekend() && $end_date->isWeekend())
            return [ 'type' => 'weekend', 'duration' => $end_date->diffInMinutes($start_date)];
        Log::info('is', [$inconvenience_settings]);
        if (!is_null($inconvenience_settings) && $inconvenience_settings->weekends_day_before_after == 'yes') {
//            Carbon::setWeekendDays([5,6,0,1]);

            $start_date_number = $start_date->dayOfWeek;
            $end_date_number = $end_date->dayOfWeek;
            $first = $start_date;
            if($start_date_number == 6)
            {
                $first = $first->subDay();
                $first = Carbon::create($first->year, $first->month, $first->day, $inconvenience_settings->weekend_start, 0, 0);
            }
            $second = $end_date;
            if($end_date_number == 0)
            {
                $second = $second->addDay();
                $second = Carbon::create($second->year, $second->month, $second->day, $inconvenience_settings->weekend_end, 0, 0);
            }
            if ($start_date->between($first, $second))
            {
                $start_time_in_inconvenience = true;
                $type = 'weekend';
            }
            if ($end_date->between($first, $second))
            {
                $end_time_in_inconvenience = true;
                $type = 'weekend';
            }
        }

        if ($holidays && !$start_time_in_inconvenience && !$end_time_in_inconvenience)
            foreach ($holidays as $holiday) {
                $all_holidays = Holiday::where('code', $holiday->holiday_code)->get();
                foreach ($all_holidays as $one_holiday) {
                    $first = Carbon::createFromFormat('Y-m-d', $one_holiday->date_from);
                    if ($inconvenience_settings->holiday_day_before_after == 'yes')
                        $first = $first->subDay();
                    $first = Carbon::create($first->year, $first->month, $first->day, $holiday_start, 0, 0);
                    $second = Carbon::createFromFormat('Y-m-d', $one_holiday->date_to);
                    $second = $second->addDay();
                    $second = Carbon::create($second->year, $second->month, $second->day, $holiday_end, 0, 0);
                    if ($start_date->between($first, $second))
                    {
                        $start_time_in_inconvenience = true;
                        $type = 'holiday';
                    }
                    if ($end_date->between($first, $second))
                    {
                        $end_time_in_inconvenience = true;
                        $type = 'holiday';
                    }
                }
            }

        if (!$start_time_in_inconvenience && !$end_time_in_inconvenience) {
            $first = Carbon::create($start_date->year, $start_date->month, $start_date->day, $weekdays_start, 0, 0);
            $second = Carbon::create($end_date->year, $end_date->month, $end_date->day, $weekdays_end, 0, 0);

            if ($start_date->isSameDay($end_date) && $start_date->between($second, $first) && $end_date->between($second, $first))
                return 0;

            if ($start_date->isSameDay($end_date) && $end_date->gte($first))
                $second = $second->addDay();
            elseif ($start_date->isSameDay($end_date) && $second->gte($start_date))
                $first = $first->subDay();

            if ($start_date->between($first, $second)) {
                $start_time_in_inconvenience = true;
                $type = 'weekday';
            }
            if ($end_date->between($first, $second)) {
                $end_time_in_inconvenience = true;
                $type = 'weekday';
            }
        }

        if ($start_time_in_inconvenience && $end_time_in_inconvenience)
            return [ 'type' => $type, 'duration' => $end_date->diffInMinutes($start_date)];
        elseif ($start_time_in_inconvenience && !$end_time_in_inconvenience)
            return [ 'type' => $type, 'duration' => $start_date->diffInMinutes($second)];
        elseif (!$start_time_in_inconvenience && $end_time_in_inconvenience)
            return [ 'type' => $type, 'duration' => $first->diffInMinutes($end_date)];

    }

}

