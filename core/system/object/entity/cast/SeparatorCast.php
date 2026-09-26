<?php

declare(strict_types=1);

namespace AC\core\system\object\entity\cast;

/**
 * Class ArrayCast
 */
class SeparatorCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): array
  {
    if (is_string($value)) {
      $value = explode($params['separator'] ?? ';', $value);
    }

    return (array)$value;
  }

  /**
   * {@inheritDoc}
   */
  public static function set($value, array $params = []): string
  {
    return implode($params['separator'] ?? ';', (array)$value);
  }
}
