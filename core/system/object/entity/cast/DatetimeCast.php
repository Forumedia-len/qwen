<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

use AC\core\system\i18n\Time;
use DateTime;
use Exception;

/**
 * Class DatetimeCast
 */
class DatetimeCast extends BaseCast
{
  /**
   * {@inheritDoc}
   *
   * @return Time
   *
   * @throws Exception
   */
  public static function get($value, array $params = [])
  {
    if ($value instanceof Time) {
      return $value;
    }

    if ($value instanceof DateTime) {
      return Time::createFromInstance($value);
    }

    if (is_numeric($value)) {
      return Time::createFromTimestamp((int)$value);
    }

    if (is_string($value)) {
      return Time::parse($value);
    }

    return $value;
  }
}
