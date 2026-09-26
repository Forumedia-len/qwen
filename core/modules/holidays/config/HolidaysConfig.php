<?php

namespace AC\core\modules\holidays\config;

use AC\core\system\config\BaseConfig;

class HolidaysConfig extends BaseConfig
{
  public function useHolidayAsSanday(): bool
  {
    return HOLIDAY_LIKE_SUNDAY || MC_ARENA;
  }

  public function checkBlockingEventsForHolidays(): bool
  {
    return !defined('CHECK_BLOCKING_EVENTS_FOR_HOLIDAYS') || CHECK_BLOCKING_EVENTS_FOR_HOLIDAYS;
  }

  public function useSundayTimes(): bool
  {
    static $checkField;
    if ($checkField === null) {
      $checkField = getEngine('holidays')->checkField('sunday_times');
    }
    return defined('HOLIDAY_SUNDAY_TIMES') && HOLIDAY_SUNDAY_TIMES && $checkField;
  }
}