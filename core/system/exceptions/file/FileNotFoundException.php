<?php

namespace AC\core\system\exceptions\file;

use AC\core\system\exceptions\DebugTraceableTrait;
use AC\core\system\exceptions\ExceptionInterface;
use RuntimeException;

class FileNotFoundException extends RuntimeException implements ExceptionInterface
{
  use DebugTraceableTrait;

  public static function forFileNotFound($path)
  {
    return new static(lang('fileNotFound', 'files_error', [$path]));
  }
}
