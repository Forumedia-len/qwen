<?php

namespace AC\core\system\exceptions\file;

use AC\core\system\exceptions\DebugTraceableTrait;
use AC\core\system\exceptions\ExceptionInterface;
use RuntimeException;

class FileException extends RuntimeException implements ExceptionInterface
{
  use DebugTraceableTrait;

  public static function forUnableToMove($from = null, $to = null, $error = null)
  {
    return new static(lang('cannotMove', 'files_error', [$from, $to, $error]));
  }
}
