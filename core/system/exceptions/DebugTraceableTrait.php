<?php

namespace AC\core\system\exceptions;

use Exception;
/**
 * This trait provides framework exceptions the ability to pinpoint
 * accurately where the exception was raised rather than instantiated.
 *
 * This is used primarily for factory-instantiated exceptions.
 */
trait DebugTraceableTrait
{
    /**
     * Tweaks the exception's constructor to assign the file/line to where
     * it is actually raised rather than were it is instantiated.
     */
    final public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $trace = $this->getTrace()[0];

        if (isset($trace['class']) && $trace['class'] === static::class) {
          list($this->line, $this->file) = array($trace['line'], $trace['file']);
        }
    }
}
