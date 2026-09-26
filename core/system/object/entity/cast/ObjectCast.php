<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

/**
 * Class ObjectCast
 */
class ObjectCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): object
  {
    return (object)$value;
  }
}
