<?php

namespace AC\core\modules\holidays\helpers;

use AC\core\modules\holidays\config\HolidaysConfig;
use AC\core\modules\holidays\entities\enums\HolidayScheduleType;
use AC\core\system\helpers\CalendarHelper;
use InvalidArgumentException;

/**
 * Преобразования дат с учётом правил праздничных дней.
 */
final class HolidayHelper
{
  private const SUNDAY_WEEKDAY = 6;

  /**
   * Request-local кеш решения «использовать воскресенье?» по ключу Y-m-d + Prices|Times.
   *
   * @var array<string, bool>
   */
  private static array $sundayScheduleCache = [];

  /**
   * Возвращает день недели, расписание выбранного типа которого действует для даты.
   *
   * @param int|string $date              Дата или Unix timestamp.
   * @param bool|null  $useSundaySchedule Явно заданный режим воскресного расписания.
   */
  public static function resolveWeekday(
    int|string $date,
    HolidayScheduleType $type,
    ?bool $useSundaySchedule = null
  ): int {
    $unixTime = self::normalizeUnixTime($date);

    if ($useSundaySchedule ?? self::usesSundayScheduleForDate($unixTime, $type)) {
      return self::SUNDAY_WEEKDAY;
    }

    return CalendarHelper::getWeekdayByUnixtime($unixTime);
  }

  /**
   * Проверяет глобальную настройку и флаг выбранного типа у праздника.
   * Результат кешируется в рамках запроса по календарной дате и режиму.
   */
  public static function usesSundayScheduleForDate(int|string $date, HolidayScheduleType $type): bool
  {
    $unixTime = self::normalizeUnixTime($date);
    $key      = self::buildSundayScheduleCacheKey($unixTime, $type);

    if (array_key_exists($key, self::$sundayScheduleCache)) {
      return self::$sundayScheduleCache[$key];
    }

    return self::$sundayScheduleCache[$key] = self::computeUsesSundayScheduleForDate($unixTime, $type);
  }

  /**
   * Сбрасывает request-local кеш праздничных решений.
   */
  public static function clearSundayScheduleCache(): void
  {
    self::$sundayScheduleCache = [];
  }

  private static function computeUsesSundayScheduleForDate(int $unixTime, HolidayScheduleType $type): bool
  {
    /** @var HolidaysConfig $holidaysConfig */
    $holidaysConfig = config('holidays');
    $enabled        = match ($type) {
      HolidayScheduleType::Prices => $holidaysConfig->useHolidayAsSanday(),
      HolidayScheduleType::Times  => $holidaysConfig->useSundayTimes(),
    };

    return $enabled
      && getEngine('holidays')->checkSundayHoliday($unixTime, $type->value);
  }

  private static function buildSundayScheduleCacheKey(int $unixTime, HolidayScheduleType $type): string
  {
    return date('Y-m-d', $unixTime) . ':' . $type->value;
  }

  private static function normalizeUnixTime(int|string $date): int
  {
    $unixTime = is_int($date) ? $date : strtotime($date);
    if ($unixTime === false) {
      throw new InvalidArgumentException('Invalid holiday date.');
    }

    return $unixTime;
  }
}
