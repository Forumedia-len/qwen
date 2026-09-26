<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

use AC\core\system\exceptions\cast\CastException;

/**
 * Class TimestampCast
 */
class TimestampCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = [])
  {
    $value = strtotime($value);

    if ($value === false) {
      throw CastException::forInvalidTimestamp();
    }

    return $value;
  }
}
