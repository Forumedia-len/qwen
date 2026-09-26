<?php

namespace AC\core\system\helpers;

use AC\app\locators\Service;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use DateTime;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use const ABO14_TYPE;

class CalendarHelper
{
  public static function getDaysInMonth($year, $month): int
  {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
  }
  
  public static function getDaysInMonthByDate($date): int
  {
    $year  = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    return self::getDaysInMonth($year, $month);
  }

//день недели по timestamp, 0 - понедельник
  public static function getWeekdayByUnixTime($unixTime)
  {
    if (!is_numeric($unixTime)) {
      $unixTime = strtotime($unixTime);
    }
    $weekday_calendar = date('w', $unixTime);
    if ($weekday_calendar == 0) {
      $weekday_calendar = 7;
    }
    
    return --$weekday_calendar;
  }
  
  
  //Находит количество нужных дней недели в промежутке времени
//$space - во сколько недель считать данный день (например раз в три недели, если $space = 3)
  public static function getCountWeekDays($period, $weekday, $date_start, $date_finish, $space = 1, $blocks = [], $first_game = null)
  {
    $count           = [];
    $current_date    = strtotime($date_start);
    $date_start      = $current_date;
    $date_finish     = strtotime($date_finish);
    $ticket_time_end = TimeHelper::convertTime24(date('H:i:s', $date_finish));
    $ticket_data_end = date('Y-m-d', $date_start);
    
    $ticket_current = $date_start;

//	$ticket_end = mktime(date('H', $date_finish), (date('i', $date_finish)-$period), date('s', $date_finish), date('m', $date_start) , date('d', $date_start) , date('Y', $date_start));
    $ticket_end = strtotime($ticket_data_end . ' ' . $ticket_time_end . ' - ' . $period . ' minutes');
    
    while ($ticket_current <= $ticket_end) {
      $ticket_time[date('H:i:s', $ticket_current)] = 1;
      $ticket_current                              = mktime(
        date('H', $ticket_current),
        (date('i', $ticket_current) + $period),
        date('s', $ticket_current),
        date('m', $current_date),
        date('d', $current_date),
        date('Y', $current_date)
      );
    }
    if ($first_game == null) {
      $first_game = self::getFirstDayTicket($date_start, $weekday);
    }
    while ($current_date <= $date_finish) {
      if (CalendarHelper::getWeekdayByUnixtime($current_date) == $weekday) {
        $current_date_minus_time = strtotime(date('Y-m-d', $current_date));
        foreach ($blocks as $b) {
          if (isset($b[0]) && isset($b[1])) {
            $block_start_tmp = mktime(
              0,
              0,
              0,
              date('m', strtotime($b[0])),
              date('d', strtotime($b[0])),
              date('Y', strtotime($b[0]))
            );
            $block_end_tmp   = mktime(
              0,
              0,
              0,
              date('m', strtotime($b[1])),
              date('d', strtotime($b[1])),
              date('Y', strtotime($b[1]))
            );
            if ($block_start_tmp <= $current_date && $current_date <= $block_end_tmp) {
              $block_current = strtotime($b[0]);
              $block_end     = strtotime($b[1]);
              $block_end     = mktime(
                date('H', $block_end),
                (date('i', $block_end) - $period),
                date('s', $block_end),
                date('m', $block_end),
                date('d', $block_end),
                date('Y', $block_end)
              );
              $block_tmp     = [];
              while ($block_current <= $block_end) {
                $block_tmp[date('Y-m-d', $block_current)][] = date('H:i:s', $block_current);
                $block_current                              = mktime(
                  date('H', $block_current),
                  (date('i', $block_current) + $period),
                  date('s', $block_current),
                  date('m', $block_current),
                  date('d', $block_current),
                  date('Y', $block_current)
                );
              }
              foreach ($ticket_time as $time => $c) {
                if (isset($block_tmp[date('Y-m-d', $current_date)]) && in_array($time,
                    $block_tmp[date('Y-m-d', $current_date)]) && self::checkDayForTicket($current_date_minus_time, $first_game[$weekday], $space)) {
                  $ticket_time[$time] = 0;
                }
              }
              unset($block_tmp);
            }
          }
        }
        if (self::checkDayForTicket($current_date_minus_time, $first_game[$weekday], $space)) {
          foreach ($ticket_time as $time => $c) {
            if (!isset($count[date('Y-m-d', $current_date)][$time])) {
              $count[date('Y-m-d', $current_date)][$time] = 0;
            }
            $count[date('Y-m-d', $current_date)][$time] += $c;
            $ticket_time[$time]                         = 1;
          }
        }
      }
      
      $current_date = mktime(0, 0, 0, date('m', $current_date), (date('d', $current_date) + 1), date('Y', $current_date));
    }
    
    return $count;
  }
  
  /**
   * По ISO 8601 первая неделя не та, что начинается с первого января,
   * а та, что содержит первый четверг года. Сам в шоке)
   */
  public static function getCountWeek($year)
  {
    $date = date('w', mktime(0, 0, 0, 12, 31, $year));
    $day  = ($date < 4 ? 31 - $date : 31);
    
    return date('W', mktime(0, 0, 0, 12, $day, $year)) + ($date < 4 ? 1 : 0);
  }
  
  public static function checkDayForTicket($unixCurrentData, $unixFirstDayTicketByWeekday, $space)
  {
    return (round((abs($unixCurrentData - $unixFirstDayTicketByWeekday)) / (86400 * 7)) % $space == 0);
  }
  
  public static function getFirstDayTicket($start, $weekdays, $week = null, $unix = true)
  {
    if ($week == null) {
      $week = (int)date('W', strtotime($start));
    }
    
    $year     = date('o', strtotime($start));
    $weekdays = !is_array($weekdays) ? explode(',', $weekdays) : $weekdays;
    
    $first_day = [];
    foreach ($weekdays as $w) {
      $weekday = $w + 1;
      $genDate = new DateTime('00:00:00');
      $dk      = (int)(($weekday < date('N', strtotime($start))) && (ABO14_TYPE == 2));
      $genDate->setISODate($year, $week + $dk, $weekday);
      $first_day[$w] = $unix ? $genDate->getTimestamp() : $genDate->format('Y-m-d');
    }
    
    return $first_day;
  }
  
  public static function getSeasonByDate($date, $seasons = []): int
  {
    $seasons = empty($seasons) ? Service::engines()->areas->getSeasonsStart() : $seasons;
    $keys    = array_keys($seasons);
    $_date   = strtotime('1970-' . date('m-d', strtotime($date)));
    for ($i = 0; $i < count($keys); $i++) {
      $start = strtotime('1970-' . date('m-d', strtotime($seasons[$keys[$i]])));
      $end   = $i + 1 < count($keys)
        ? strtotime('1970-' . date('m-d', strtotime($seasons[$keys[$i + 1]])))
        : strtotime('1970-12-31');
      if ($_date >= $start && $_date < $end) {
        return $keys[$i];
      }
    }
    
    return end($keys);
  }
  
  /**
   * @throws \Exception
   * @throws InvalidArgumentException
   */
  public static function getWorkDaysByPeriod(
    $date_start,
    $date_finish,
    ?array $weekdays = null,
    ?array $first_days = null,
    mixed $space = 1,
    array $workPeriods = [],
    bool $byWeekday = true
  ): array {
    $normalizedSpace = filter_var($space, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ((!is_int($space) && !is_string($space)) || $normalizedSpace === false) {
      throw new InvalidArgumentException('Space must be a positive integer.');
    }
    $space = $normalizedSpace;

    $work_days   = [];
    $date_start  = new DateTimeImmutable($date_start);
    $date_finish = new DateTimeImmutable($date_finish);
    if (empty($weekdays)) {
      $weekdays = [0, 1, 2, 3, 4, 5, 6];
    }
    if (empty($first_days)) {
      $first_days = self::getFirstDayTicket($date_start->format('Y-m-d'), $weekdays, null, false);
    }
    foreach ($weekdays as $weekday) {
      $current = new DateTimeImmutable($first_days[$weekday]);
      while ($current > $date_start) {
        $current = $current->modify('-' . $space . ' week');
      }
      while ($current < $date_start) {
        $current = $current->modify('+' . $space . ' week');
      }
      while ($current <= $date_finish) {
        $currentDate = $current->format('Y-m-d');
        if (empty($workPeriods)) {
          if ($byWeekday) {
            $work_days[$weekday][] = $currentDate;
          } else {
            $work_days[$currentDate] = $weekday;
          }
        } else {
          foreach ($workPeriods as $workPeriod) {
            if ($workPeriod['start'] <= $currentDate && $currentDate <= $workPeriod['finish']) {
              if ($byWeekday) {
                $work_days[$weekday][] = $currentDate;
              } else {
                $work_days[$currentDate] = $weekday;
              }
            }
          }
        }
        $current = $current->modify('+' . $space . ' week');
      }
    }
    return $work_days;
  }
}
