<?php

declare(strict_types = 1);

namespace AC\core\system\object\entity\cast;

use AC\core\system\exceptions\cast\CastException;
use JsonException;
use stdClass;

/**
 * Class JsonCast
 */
class JsonCast extends BaseCast
{
  /**
   * {@inheritDoc}
   */
  public static function get($value, array $params = [])
  {
    $associative = in_array('array', $params, true);

    $tmp = $value !== null ? ($associative ? [] : new stdClass()) : [];

    if (function_exists('json_decode')
      && (
        (is_string($value)
          && strlen($value) > 1
          && in_array($value[0], ['[', '{', '"'], true))
        || is_numeric($value)
      )
    ) {
      try {
        $tmp = json_decode($value, $associative, 512, JSON_THROW_ON_ERROR);
      } catch (JsonException $e) {
        throw CastException::forInvalidJsonFormat($e->getCode());
      }
    }

    return $tmp;
  }

  /**
   * {@inheritDoc}
   */
  public static function set($value, array $params = []): string
  {
    if (function_exists('json_encode')) {
      try {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
      } catch (JsonException $e) {
        throw CastException::forInvalidJsonFormat($e->getCode());
      }
    }

    return $value;
  }
}
