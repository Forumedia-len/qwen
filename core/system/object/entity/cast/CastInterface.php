<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Interface CastInterface
 *
 * The methods work at (1)(4) only.
 *   [App Code] --- (1) --> [Entity] --- (2) --> [Database]
 *   [App Code] <-- (4) --- [Entity] <-- (3) --- [Database]
 */
interface CastInterface
{
  /**
   * Takes a raw value from Entity, returns its value for PHP.
   *
   * @param array|bool|float|int|object|string|null $value Data
   * @param array $params Additional param
   *
   * @return array|bool|float|int|object|string|null
   */
  public static function get($value, array $params = []);

  /**
   * Takes a PHP value, returns its raw value for Entity.
   *
   * @param array|bool|float|int|object|string|null $value Data
   * @param array $params Additional param
   *
   * @return array|bool|float|int|object|string|null
   */
  public static function set($value, array $params = []);
}
