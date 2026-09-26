<?php

namespace AC\core\system\exceptions;

use RuntimeException;

/**
 * Class DownloadException
 */
class DownloadException extends RuntimeException implements ExceptionInterface
{
    use DebugTraceableTrait;

    public static function forCannotSetFilePath($path)
    {
        return new static(lang('cannot_set_filepath', 'http_error', [$path]));
    }

    public static function forCannotSetBinary()
    {
        return new static(lang('cannot_set_binary', 'http_error'));
    }

    public static function forNotFoundDownloadSource()
    {
        return new static(lang('not_found_download_source', 'http_error'));
    }

    public static function forCannotSetCache()
    {
        return new static(lang('cannot_set_cache', 'http_error'));
    }

    public static function forCannotSetStatusCode($code, $reason)
    {
        return new static(lang('cannot_set_status_code', 'http_error', [$code, $reason]));
    }
}
