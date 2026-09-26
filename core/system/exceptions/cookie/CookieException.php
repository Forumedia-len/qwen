<?php

namespace AC\core\system\exceptions\cookie;

use AC\core\system\exceptions\BaseException;

/**
 * CookieException is thrown for invalid cookies initialization and management.
 */
class CookieException extends BaseException
{
    /**
     * Thrown for invalid type given for the "Expires" attribute.
     *
     * @return static
     */
    public static function forInvalidExpiresTime($type)
    {
        return new static(lang('invalidExpiresTime', 'cookie_error', [$type]));
    }

    /**
     * Thrown when the value provided for "Expires" is invalid.
     *
     * @return static
     */
    public static function forInvalidExpiresValue()
    {
        return new static(lang('invalidExpiresValue', 'cookie_error'));
    }

    /**
     * Thrown when the cookie name contains invalid characters per RFC 2616.
     *
     * @return static
     */
    public static function forInvalidCookieName($name)
    {
        return new static(lang('invalidCookieName', 'cookie_error', [$name]));
    }

    /**
     * Thrown when the cookie name is empty.
     *
     * @return static
     */
    public static function forEmptyCookieName()
    {
        return new static(lang('emptyCookieName', 'cookie_error'));
    }

    /**
     * Thrown when using the `__Secure-` prefix but the `Secure` attribute
     * is not set to true.
     *
     * @return static
     */
    public static function forInvalidSecurePrefix()
    {
        return new static(lang('invalidSecurePrefix', 'cookie_error'));
    }

    /**
     * Thrown when using the `__Host-` prefix but the `Secure` flag is not
     * set, the `Domain` is set, and the `Path` is not `/`.
     *
     * @return static
     */
    public static function forInvalidHostPrefix()
    {
        return new static(lang('invalidHostPrefix', 'cookie_error'));
    }

    /**
     * Thrown when the `SameSite` attribute given is not of the valid types.
     *
     * @return static
     */
    public static function forInvalidSameSite($sameSite)
    {
        return new static(lang('invalidSameSite', 'cookie_error', [$sameSite]));
    }

    /**
     * Thrown when the `SameSite` attribute is set to `None` but the `Secure`
     * attribute is not set.
     *
     * @return static
     */
    public static function forInvalidSameSiteNone()
    {
        return new static(lang('invalidSameSiteNone', 'cookie_error'));
    }

    /**
     * Thrown when the `CookieStore` class is filled with invalid Cookie objects.
     *
     * @param array<int|string> $data
     *
     * @return static
     */
    public static function forInvalidCookieInstance(array $data)
    {
        return new static(lang('invalidCookieInstance', 'cookie_error', $data));
    }

    /**
     * Thrown when the queried Cookie object does not exist in the cookie collection.
     *
     * @param string[] $data
     *
     * @return static
     */
    public static function forUnknownCookieInstance(array $data)
    {
        return new static(lang('unknownCookieInstance', 'cookie_error', $data));
    }
}
