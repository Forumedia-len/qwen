<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class IntegerCast
 */
class IntegerCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): int
  {
    return (int)$value;
  }
}
