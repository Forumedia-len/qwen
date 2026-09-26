<?php

namespace AC\core\system\helpers;

use AC\core\system\object\StdObject;
use DateTimeImmutable;
use InvalidArgumentException;

class DateHelper extends DateTimeHelper
{
  /**
   * Создаёт неизменяемый объект даты из строки формата Y-m-d.
   *
   * @throws InvalidArgumentException
   */
  public static function parseDate(string $date): DateTimeImmutable
  {
    $result = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($result === false || $result->format('Y-m-d') !== $date) {
      throw new InvalidArgumentException('Incorrect date: ' . $date);
    }

    return $result;
  }

  /**
   * Вычитает включительные диапазоны дат из исходного диапазона.
   *
   * @param array<int, array{start: string, finish: string}> $excludedRanges
   *
   * @return array<int, array{start: string, finish: string}>
   * @throws InvalidArgumentException
   */
  public static function subtractRanges(string $start, string $finish, array $excludedRanges): array
  {
    $rangeStart = self::parseDate($start);
    $rangeFinish = self::parseDate($finish);
    if ($rangeStart > $rangeFinish) {
      throw new InvalidArgumentException('Incorrect date range.');
    }

    $normalizedRanges = [];
    foreach ($excludedRanges as $excludedRange) {
      if (!isset($excludedRange['start'], $excludedRange['finish'])) {
        throw new InvalidArgumentException('Incorrect excluded date range.');
      }

      $excludedStart = self::parseDate($excludedRange['start']);
      $excludedFinish = self::parseDate($excludedRange['finish']);
      if ($excludedStart > $excludedFinish) {
        throw new InvalidArgumentException('Incorrect excluded date range.');
      }
      if ($excludedFinish < $rangeStart || $excludedStart > $rangeFinish) {
        continue;
      }

      if ($excludedStart < $rangeStart) {
        $excludedStart = $rangeStart;
      }
      if ($excludedFinish > $rangeFinish) {
        $excludedFinish = $rangeFinish;
      }
      $normalizedRanges[] = ['start' => $excludedStart, 'finish' => $excludedFinish];
    }

    usort(
      $normalizedRanges,
      static fn(array $left, array $right): int => $left['start'] <=> $right['start']
        ?: $left['finish'] <=> $right['finish']
    );

    $ranges = [];
    $cursor = $rangeStart;
    foreach ($normalizedRanges as $excludedRange) {
      if ($excludedRange['finish'] < $cursor) {
        continue;
      }
      if ($excludedRange['start'] > $cursor) {
        $ranges[] = [
          'start' => $cursor->format('Y-m-d'),
          'finish' => $excludedRange['start']->modify('-1 day')->format('Y-m-d'),
        ];
      }

      $nextCursor = $excludedRange['finish']->modify('+1 day');
      if ($nextCursor > $cursor) {
        $cursor = $nextCursor;
      }
      if ($cursor > $rangeFinish) {
        break;
      }
    }

    if ($cursor <= $rangeFinish) {
      $ranges[] = [
        'start' => $cursor->format('Y-m-d'),
        'finish' => $rangeFinish->format('Y-m-d'),
      ];
    }

    return $ranges;
  }

  /** Преобразовать дату из строки в объект
   *
   * @param null|string $date - data in format 'Y-m-d'
   * @param null|array  $default
   *
   * @return StdObject
   */
  public static function renderDateStringAsObject($date = null, $default = null)
  {
    $unix  = strtotime($date ?? '');
    $_date = [
      'day'   => $unix !== false ? date('d', $unix) : ($default !== null ? $default['day'] : date('d')),
      'month' => $unix !== false ? date('m', $unix) : ($default !== null ? $default['month'] : date('m')),
      'year'  => $unix !== false ? date('Y', $unix) : ($default !== null ? $default['year'] : date('Y')),
    ];

    return ObjectHelper::createObject($_date, true);
  }

  /** Преобразовать дату из объекта в строку формата 'Y-m-d'
   *
   * @param null|object $date
   *
   * @return string
   */
  public static function renderDateObjectAsString($date = null)
  {
    return $date && is_object($date) ? $date->year . '-' . $date->month . '-' . $date->day : date('Y-m-d');
  }

  /**
   *  проверить входит ли дата бронирования в указанный промежуток
   *  Все даты передаются в формате mysql_date
   *
   * @param $date        - дата для проверки
   * @param $date_start  - начальная дата промежутка
   * @param $date_finish - конечная дата промежутка
   *
   * @return bool
   */
  public static function checkDurationByDate($date, $date_start, $date_finish)
  {
    return strtotime($date) >= strtotime($date_start)
      && strtotime($date) <= strtotime($date_finish);
  }

  //Это нужно для дней рождения, т.к. date только до 1970г
  public static function convertMysql2Date($mysql_date)
  {
    return self::validateDate($mysql_date, 'Y-m-d', $d) ? $d->format('d.m.Y') : null;
  }

  public static function convertDate2Mysql($euro_date)
  {
    return self::validateDate($euro_date, 'd.m.Y', $d) ? $d->format('Y-m-d') : null;
  }


  public static function getWorkingDaysForPeriod($date_start, $date_finish, $weekdays = [])
  {
    $days            = [];
    $startUnixTime   = strtotime($date_start);
    $endUnixTime     = strtotime($date_finish);
    $currentUnixTime = $startUnixTime;
    while ($currentUnixTime <= $endUnixTime) {
      if (empty($weekdays) || isset($weekdays[CalendarHelper::getWeekdayByUnixtime($currentUnixTime)])) {
        $days[] = date('Y-m-d', $currentUnixTime);
      }
      $currentUnixTime = mktime(0, 0, 0,
        date('m', $currentUnixTime),
        (int)date('d', $currentUnixTime) + 1, date('Y', $currentUnixTime));
    }

    return $days;
  }

  public static function yearsAsArray(int $startYear, int $endYear): array
  {
    $result = [];
    for ($year = $startYear; $year <= $endYear; $year++) {
      $result[$year] = $year;
    }

    return $result;
  }

  public static function normalizeYear($year): int
  {
    if ($year && self::validateDate((string)$year, 'Y')) {
      return (int)$year;
    }

    return (int)date('Y');
  }

  public static function monthsAsArray(int $startMonth = 1, int $endMonth = 12, $asString = false): array
  {
    $result = [];
    for ($month = $startMonth; $month <= $endMonth; $month++) {
      $result[sprintf('%02d', $month)] = $asString ?
        TranslateHelper::translateMonth($month) :
        sprintf('%02d', $month);
    }

    return $result;
  }

  public static function normalizeMonth($month, $asString = false): int
  {
    if ($month && date('m', strtotime($month) == $month)) {
      $month = (int)$month;
    }
    $month = (int)($month ?: date('m'));

    return $asString ? sprintf('%02d', $month) : $month;
  }

  public static function daysAsArray($startDay, $endDay): array
  {
    $result = [];
    for ($day = $startDay; $day <= $endDay; $day++) {
      $result[sprintf('%02d', $day)] = sprintf('%02d', $day);
    }

    return $result;
  }

  public static function normalizeDay($day, $asString = false): int
  {
    if ($day && date('d', strtotime($day) == $day)) {
      $day = (int)$day;
    }
    $day = (int)($day ?: date('d'));

    return $asString ? sprintf('%02d', $day) : $day;
  }

  public static function addYear(int $value = 0, ?int $year = null): int
  {
    return (int)($year ?? date('Y')) + $value;
  }

  public static function addDay(string $date_finish): string
  {
    return date('Y-m-d', strtotime($date_finish) + 86400);
  }

}
