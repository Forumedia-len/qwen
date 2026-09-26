<?php

namespace AC\core\system\helpers;

class NumberHelper
{
  /**
   * Упрощение форматирование числа - внесены часто используемые значения
   * @param        $number
   * @param int    $decimals
   * @param string $decimal_separator
   * @param string $thousands_separator
   *
   * @return string
   */
  public static function format($number, int $decimals = 2, string $decimal_separator = ',', string $thousands_separator = ''): string
  {
    return number_format($number, $decimals, $decimal_separator, $thousands_separator);
  }
  
  /**
   * Приведение строки к типу float - данные поступают с сайта с запятой в качестве разделителя
   *
   * @param string $var
   * @return float
   */
  public static function float(string $var): float
  {
    return round((float)str_replace(',', '.', trim($var)), 2);
  }
  
  public static function valute($value, int $decimals = 2, string $decimal_separator = ',', string $thousands_separator = ' '): string
  {
    return self::format($value, $decimals, $decimal_separator, $thousands_separator) . ' ' . CURR_VALUTE;
  }
}
