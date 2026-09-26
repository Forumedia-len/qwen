<?php

namespace AC\core\system\exceptions\format;

use AC\core\system\exceptions\DebugTraceableTrait;
use AC\core\system\exceptions\ExceptionInterface;
use RuntimeException;

/**
 * FormatException
 */
class FormatException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Thrown when the instantiated class does not exist.
     *
     * @return FormatException
     */
    public static function forInvalidFormatter($class)
    {
        return new static(lang('invalidFormatter', 'format_error', [$class]));
    }

    /**
     * Thrown in JSONFormatter when the json_encode produces
     * an error code other than JSON_ERROR_NONE and JSON_ERROR_RECURSION.
     *
     * @param string $error
     *
     * @return FormatException
     */
    public static function forInvalidJSON($error = null)
    {
        return new static(lang('invalidJSON', 'format_error', [$error]));
    }

    /**
     * Thrown when the supplied MIME type has no
     * defined Formatter class.
     *
     * @return FormatException
     */
    public static function forInvalidMime($mime)
    {
        return new static(lang('invalidMime', 'format_error', [$mime]));
    }

    /**
     * Thrown on XMLFormatter when the `simplexml` extension
     * is not installed.
     *
     * @return FormatException
     *
     * @codeCoverageIgnore
     */
    public static function forMissingExtension()
    {
        return new static(lang('missingExtension', 'format_error'));
    }
}
