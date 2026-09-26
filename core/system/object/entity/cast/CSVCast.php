<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class CSVCast
 */
class CSVCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): array
  {
    return explode(',', $value);
  }

  /**
   * {@inheritDoc}
   */
  public static function set($value, array $params = []): string
  {
    return implode(',', $value);
  }
}
