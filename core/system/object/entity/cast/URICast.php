<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

use AC\core\system\http\url\URI;

/**
 * Class URICast
 */
class URICast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = []): URI
  {
    return $value instanceof URI ? $value : new URI($value);
  }
}
