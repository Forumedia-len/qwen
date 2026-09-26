<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class BooleanCast
 */
class BooleanCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): bool
  {
    return (bool)$value;
  }
}
