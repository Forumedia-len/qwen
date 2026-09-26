<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class FloatCast
 */
class FloatCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): float
  {
    return (float)$value;
  }
}
