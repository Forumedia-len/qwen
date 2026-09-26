<?php

namespace AC\core\system\exceptions;

use RuntimeException;

class BaseException extends RuntimeException implements ExceptionInterface
{
  use DebugTraceableTrait;

  public static function forEnabledZlibOutputCompression()
  {
    return new static(lang('enabledZlibOutputCompression', 'core_error'));
  }

  public static function forInvalidFile($path)
  {
    return new static(lang('invalidFile', 'core_error', [$path]));
  }

  public static function forCopyError($path)
  {
    return new static(lang('copyError', 'core_error', [$path]));
  }

  public static function forMissingExtension($extension)
  {
    if (strpos($extension, 'intl') !== false) {
      // @codeCoverageIgnoreStart
      $message = sprintf(
        'The framework needs the following extension(s) installed and loaded: %s.',
        $extension
      );
      // @codeCoverageIgnoreEnd
    } else {
      $message = lang('missingExtension', 'core_error', [$extension]);
    }

    return new static($message);
  }

  public static function forNoHandlers($class)
  {
    return new static(lang('noHandlers', 'core_error', [$class]));
  }

  public static function forFabricatorCreateFailed($table, $reason)
  {
    return new static(lang('Fabricator.createFailed', 'core_error', [$table, $reason]));
  }

  /**
   * @return string the user-friendly name of this exception
   */
  public function getName()
  {
    return 'BaseException';
  }

}