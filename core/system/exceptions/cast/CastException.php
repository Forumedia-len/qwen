<?php

declare(strict_types = 1);

namespace AC\core\system\exceptions\cast;

use AC\core\system\exceptions\BaseException;
use AC\core\system\exceptions\HasExitCodeInterface;

/**
 * CastException is thrown for invalid cast initialization and management.
 */
class CastException extends BaseException implements HasExitCodeInterface
{
  public function getExitCode(): int
  {
    return EXIT_CONFIG;
  }

  /**
   * Thrown when the cast class does not extends BaseCast.
   *
   * @return static
   */
  public static function forInvalidInterface(string $class)
  {
    return new static(lang('baseCastMissing', 'cast',[$class]));
  }

  /**
   * Thrown when the Json format is invalid.
   *
   * @return static
   */
  public static function forInvalidJsonFormat(int $error)
  {
    return match ($error) {
      JSON_ERROR_DEPTH          => new static(lang('jsonErrorDepth', 'cast')),
      JSON_ERROR_STATE_MISMATCH => new static(lang('jsonErrorStateMismatch', 'cast')),
      JSON_ERROR_CTRL_CHAR      => new static(lang('jsonErrorCtrlChar', 'cast')),
      JSON_ERROR_SYNTAX         => new static(lang('jsonErrorSyntax', 'cast')),
      JSON_ERROR_UTF8           => new static(lang('jsonErrorUtf8', 'cast')),
      default                   => new static(lang('jsonErrorUnknown', 'cast')),
    };
  }

  /**
   * Thrown when the cast method is not `get` or `set`.
   *
   * @return static
   */
  public static function forInvalidMethod(string $method)
  {
    return new static(lang('invalidCastMethod', 'cast', [$method]));
  }

  /**
   * Thrown when the casting timestamp is not correct timestamp.
   *
   * @return static
   */
  public static function forInvalidTimestamp()
  {
    return new static(lang('invalidTimestamp', 'cast'));
  }
}
