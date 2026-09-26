<?php

namespace AC\core\system\helpers;


use AC\app\locators\Service;

class TranslateHelper
{
  /**  день недели по-немецки, $prefix == true - короткий вариант */
  public static function translateWeekday($number, $prefix = false)
  {
    return lang('weekday_' . ($prefix == true ? 'small_' : '') . $number);
  }
  
  /** день недели по-немецки, $prefix == true - короткий вариант */
  public static function translateWeekdayFull($number, $prefix)
  {
    $arr_numb = explode(",", $number);
    $out      = [];
    foreach ($arr_numb as $arr_nubmer) {
      $out[] = self::translateWeekday($arr_nubmer, $prefix);
    }
    
    return implode(',', $out);
  }
  
  /** Номер дня недели по названию */
  public static function translateBackWeekday($title)
  {
    for ($i = 0; $i < 7; $i++) {
      if ($title == self::translateWeekday($i)) {
        return $i;
      }
    }
  }
  
  /**
   * Перевод названия месяца
   *
   * @param      $number      - номер месяца
   * @param bool $declination - применять к переводу склонение (для русского варианта)
   *
   * @return bool|string
   */
  public static function translateMonth($number, bool $declination = false): bool|string
  {
    return lang(($declination ? 'declination_' : '') . 'month_' . (int)$number);
  }
  
  public static function translatePeriodInMinute($period, $allocateHours = true): string
  {
    $hours   = 0;
    $minutes = $period;
    if ($allocateHours) {
      $hours   = floor($period / 60);
      $minutes = $period - ($hours * 60);
    }
    
    return ($hours > 0 ? $hours . ' ' . self::wordEndingTranslation($hours,
          'Hours') . ' ' : '') . ($minutes > 0 ? $minutes . ' ' . self::wordEndingTranslation($hours, 'Minutes') : '');
  }
  
  public static function wordEndingTranslation($number, $message, $group = null, $locale = null)
  {
    $lang = Service::lang($locale);
    $endingArray = [];
    foreach (['one', 'few', 'many', 'other'] as $item) {
      $endingArray[] =$lang->has($message . '_' . $item, $group)
        ? $lang->_($message . '_' . $item, $group)
        : $lang->_($message, $group);
    }
    
    return self::getNumEnding($number, $endingArray);
  }
  
  public static function getNumEnding($number, $endingArray)
  {
    $number = $number % 100;
    if ($number >= 11 && $number <= 19) {
      $ending = $endingArray[2];
    } else {
      $i      = $number % 10;
      $ending = match ($i) {
        1       => $endingArray[0],
        2, 3, 4 => $endingArray[1],
        default => $endingArray[2],
      };
    }
    
    return $ending;
  }
}