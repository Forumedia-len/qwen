<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class BaseCast
 */
abstract class BaseCast implements CastInterface
{
  /**
   * Get
   *
   * @param array|bool|float|int|object|string|null $value Data
   * @param array $params Additional param
   *
   * @return array|bool|float|int|object|string|null
   */
  public static function get($value, array $params = [])
  {
    return $value;
  }

  /**
   * Set
   *
   * @param array|bool|float|int|object|string|null $value Data
   * @param array $params Additional param
   *
   * @return array|bool|float|int|object|string|null
   */
  public static function set($value, array $params = [])
  {
    return $value;
  }
}
