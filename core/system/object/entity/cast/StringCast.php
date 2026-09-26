<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class StringCast
 */
class StringCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): string
  {
    return (string)$value;
  }
}
