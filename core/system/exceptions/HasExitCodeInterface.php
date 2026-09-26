<?php

declare(strict_types = 1);

namespace AC\core\system\exceptions;

/**
 * Interface for Exceptions that has exception code as exit code.
 */
interface HasExitCodeInterface
{
  /**
   * Returns exit status code.
   */
  public function getExitCode(): int;
}
