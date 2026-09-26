<?php

declare(strict_types=1);


namespace AC\core\system\log\exceptions;

use AC\core\system\exceptions\BaseException;

class LogException extends BaseException
{
    /**
     * @return static
     */
    public static function forInvalidLogLevel(string $level)
    {
        return new static(lang('Log.invalidLogLevel', [$level]));
    }

    /**
     * @return static
     */
    public static function forInvalidMessageType(string $messageType)
    {
        return new static(lang('Log.invalidMessageType', [$messageType]));
    }
}
