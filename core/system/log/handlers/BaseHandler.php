<?php

declare(strict_types=1);

namespace AC\core\system\log\handlers;

/**
 * Base class for logging
 */
abstract class BaseHandler implements HandlerInterface
{
    /**
     * Handles
     *
     * @var array
     */
    protected $handles;

    /**
     * Date format for logging
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->handles = $config['handles'] ?? [];
    }

    /**
     * Checks whether the Handler will handle logging items of this
     * log Level.
     */
    public function canHandle(string $level): bool
    {
        return in_array($level, $this->handles, true);
    }

    /**
     * Stores the date format to use while logging messages.
     */
    public function setDateFormat(string $format): HandlerInterface
    {
        $this->dateFormat = $format;

        return $this;
    }
}
