<?php

namespace AC\core\system\helpers;

class LayoutHelper
{
  /**
   *  Показать список - годы
   */
  public static function getSelectYears(int $startYear, int $endYear, ?int $currentYear = null, string $name = 'year'): string
  {
    return useLayout()::render('select', [
      'name'    => $name,
      'values'  => DateHelper::yearsAsArray($startYear, $endYear),
      'class'   => ' ',
      'current' => DateHelper::normalizeYear($currentYear)
    ], 'common');
  }
  
  /**
   *  Показать список - месяцы
   */
  public static function getSelectMonths(
    int $startMonth = 1,
    int $endMonth = 12,
    ?int $currentMonth = null,
    string $name = 'month',
    bool $asString = false
  ): string {
    return useLayout()::render('select', [
      'name'    => $name,
      'values'  => DateHelper::monthsAsArray($startMonth, $endMonth, $asString),
      'class'   => ' ',
      'current' => DateHelper::normalizeMonth($currentMonth, true),
    ], 'common');
  }
  
  /**
   *  Показать список - дни
   */
  public static function getSelectDays(int $startDay = 1, int $endDay = 31, ?int $currentDay = null, string $name = 'day'): string
  {
    return useLayout()::render('select', [
      'name'    => $name,
      'values'  => DateHelper::daysAsArray($startDay, $endDay),
      'class'   => ' ',
      'current' => DateHelper::normalizeDay($currentDay, true),
    ], 'common');
  }
}