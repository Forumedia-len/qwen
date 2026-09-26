<?php

namespace AC\core\system\helpers;

class TimeHelper extends DateTimeHelper
{
  public static function getTimeByStartFinish($times, $period, $unix = false, $getInterimPeriod = false, $separator = ' - ')
  {
    sort($times);
    $titles = $_times = $_titles = [];
    $start  = null;
    $finish = null;
    foreach ($times as $time) {
      if ($unix) {
        $time = date('H:i', $time);
      } else {
        $time = self::convertTime24($time, false);
      }
      if ($start === null) {
        $start = $time;
      }
      if ($finish !== null && $time !== $finish) {
        if ($getInterimPeriod) {
          $_titles[self::convertTime24($start, false) . $separator . self::convertTime24($finish, false)] = $_times;
          
          $_times = [];
        }
        $titles[] = self::convertTime24($start, false) . $separator . self::convertTime24($finish, false);
        $start    = $time;
      }
      $_times[] = $time;
      $finish   = date('H:i', strtotime($time . ' + ' . $period . ' minutes'));
    }
    $titles[] = self::convertTime24($start, false) . $separator . self::convertTime24($finish, false);
    
    $_titles[self::convertTime24($start, false) . $separator . self::convertTime24($finish, false)] = $_times;
    
    return $getInterimPeriod ? $_titles : $titles;
  }
  
  /**
   * Конвертация времени окончания если оно равно 24:00 используется для абонементов
   *
   * @param      $time - время в формате строки
   * @param bool $sec  нужны секунды или нет
   *
   * @return string
   */
  public static function convertTime24($time, $sec = true)
  {
    if ($time == '24:00:00' || $time == '24:00') {
      $time = '23:59:59';
    } elseif ($time == '23:59:59' || $time == '23:59:00' || $time == '23:59' || $time == '00:00:00' || $time == '00:00') {
      $time = '24:00:00';
    } else {
      $time = date('H:i:s', strtotime($time ?? ''));
    }
    
    return $sec ? $time : substr($time, 0, 5);
  }
  
  /** Сгенерировать массив со значением времени
   * от стартового до финишного с заданным шагом
   *
   *
   * @param string $start  - стартовое время
   * @param string $finish - финишное время
   * @param int    $period - шаг промежутка в минутах
   * @param bool   $second - отображать секунды
   *
   * @return array возвращает массив в формате
   *               array(
   * '07:00:00',
   * '07:30:00',
   * '08:00:00',
   * '08:30:00',
   * '09:00:00',
   * )
   */
  public static function generateArrayTimeInIncrements($start, $finish, $period, $second = true)
  {
    $times       = [];
    $unix_start  = strtotime($start);
    $unix_finish = strtotime($finish);
    while ($unix_start < $unix_finish) {
      $time       = date('H:i' . ($second ? ':00' : ''), $unix_start);
      $times[]    = $time;
      $unix_start = strtotime($time . ' + ' . $period . ' minute');
    }

    return $times;
  }
  
  static public function returnPartTimeFromStringMysqlTime($mysqlTime, $part = 'minute')
  {
    $mysqlTime = explode(':', date('H:i:s', strtotime($mysqlTime)));
    switch ($part) {
      case 'hour':
        return $mysqlTime[0];
        break;
      case 'second':
        return $mysqlTime[2];
        break;
      default:
      case 'minute':
        return $mysqlTime[1];
        break;
    }
  }
  
  /**
   *   проверить есть совмещение временных промежутков с
   *   выбранными отрезками времени
   *
   * @param $times     - массив со значением времени
   * @param $durations - массив временных отрезков
   *
   * @return bool
   */
  public static function checkDurationByTime($times, $durations)
  {
    foreach ($times as $time) {
      if (in_array(strtotime($time), $durations)) {
        return true;
      }
    }
    
    return false;
  }
  
  /** Сгенерировать текстовое обозначение временного периода
   *  с начального времени до плюс период
   *
   * @param      $time
   * @param      $period
   * @param bool $sec
   *
   * @return string
   */
  public static function generateTitleByTimeAndPeriod($time, $period, $sec = false)
  {
    return self::convertTime24($time, $sec) . ' - ' . self::getTimePlusPeriod($time, $period, $sec);
  }
  
  
  public static function getTimePlusPeriod($time, $period, $second = false)
  {
    return self::convertTime24(date('H:i', strtotime($time . ' + ' . $period . ' minutes')), $second);
  }
  
  public static function convertMySQLTimeToMinutes($time)
  {
    [$hours, $minutes] = explode(':', $time);
    
    return (int)$hours * 60 + (int)$minutes;
  }
  
  //преобразовать минуты в mysql время
  public static function convertMinutes2MySQLTime($minutes_from_midnight)
  {
    //рассчет
    $hours   = floor($minutes_from_midnight / 60);
    $minutes = $minutes_from_midnight - $hours * 60;
    
    //для формата HH:mm
    if (strlen($hours) == 1) {
      $hours = '0' . $hours;
    }
    if (strlen($minutes) == 1) {
      $minutes = '0' . $minutes;
    }
    
    return $hours . ':' . $minutes;
  }
  
  //функция с учетоб мега-косяка что 00:00 = 24:00 и наоборот
//вообще херь конечно полнейшая, дурь
  public static function addMinutes2MySQLTime($mysql_time, $minutes, $second = false): string
  {
    if ($mysql_time === '24:00') {
      $mysql_time = '00:00';
    }
    if ($mysql_time === '24:00:00') {
      $mysql_time = '00:00:00';
    }
    $a = date('H:i', strtotime($mysql_time . ' + ' . $minutes . ' minute'));
    
    return ($a === '00:00' ? '24:00' : $a) . ($second ? ':00' : '');
  }
  
  //проверить mysqlTime (формата date('H:i')) на корректность
  public static function checkMySQLTime($mysql_time, $pattern = 'H:i'): bool
  {
    return $mysql_time === date($pattern, strtotime('2000-01-01 ' . $mysql_time));
  }
  
  /** Сгенерировать массив со значением времени
   * со стартового заданным количеством
   *
   *
   * @param string $time         - стартовое время
   * @param int    $countPeriods - количество периодов
   * @param int    $period       - шаг промежутка в минутах
   * @param bool   $second       - отображать секунды
   *
   * @return array возвращает массив в формате
   *               array(
   * '07:00:00',
   * '07:30:00',
   * '08:00:00',
   * '08:30:00',
   * '09:00:00',
   * )
   */
  public static function generateTimeArraySequentiallyForGivenNumberOfPeriods($time, $countPeriods, $period, $second = true)
  {
    $times = [];
    for ($i = 0; $i < $countPeriods; $i++) {
      $times[] = $time . ($second ? ':00' : '');
      $time    = self::addMinutes2MySQLTime($time, $period);
    }
    
    return $times;
  }
  
  public static function getCurrentTime(): string
  {
    return (new \DateTimeImmutable('now'))->format('H:i:s:v');
  }
  
  /** Подсчитать количество подряд идущих промежутков
   *
   *
   * @param array $times - периоды
   * @param       $period
   * @return array
   */
  public static function countTheNumberOfConsecutivePeriods($times, $period): array
  {
    $out   = [];
    $count = 0;
    foreach (self:: generateArrayTimeInIncrements($times[0], self::addMinutes2MySQLTime($times[count($times) - 1], $period), $period,
      false) as $time) {
      if (in_array($time, $times)) {
        $count++;
      } else {
        if ($count) {
          $out[] = $count;
        }
        $count = 0;
      }
    }
    if ($count) {
      $out[] = $count;
    }
    
    return $out;
  }
}