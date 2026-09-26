<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class ArrayCast
 */
class ArrayCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): array
  {
    if (is_string($value) && (str_starts_with($value, 'a:') || str_starts_with($value, 's:'))) {
      $value = unserialize($value, ['allowed_classes' => false]);
    }

    return (array)$value;
  }

  /**
   * {@inheritDoc}
   */
  public static function set($value, array $params = []): string
  {
    return serialize($value);
  }
}
