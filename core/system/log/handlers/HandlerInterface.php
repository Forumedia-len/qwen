<?php

declare(strict_types=1);


namespace AC\core\system\log\handlers;

/**
 * Expected behavior for a Log handler
 */
interface HandlerInterface
{
    /**
     * Handles logging the message.
     * If the handler returns false, then execution of handlers
     * will stop. Any handlers that have not run, yet, will not
     * be run.
     *
     * @param string $level
     * @param string $message
     */
    public function handle($level, $message): bool;

    /**
     * Checks whether the Handler will handle logging items of this
     * log Level.
     */
    public function canHandle(string $level): bool;

    /**
     * Sets the preferred date format to use when logging.
     *
     * @return HandlerInterface
     */
    public function setDateFormat(string $format);
}
