<?php

namespace AC\core\system\exceptions\http;


use AC\core\system\exceptions\ExceptionInterface;
use RuntimeException;

use function lang;

/**
 * Things that can go wrong with HTTP
 */
class HTTPException extends RuntimeException implements ExceptionInterface
{
  /**
   * For CurlRequest
   *
   * @return HTTPException
   *
   * @codeCoverageIgnore
   */
  public static function forMissingCurl()
  {
    return new static(lang('missing_curl', 'http_error'));
  }

  /**
   * For CurlRequest
   *
   * @return HTTPException
   */
  public static function forSSLCertNotFound($cert)
  {
    return new static(lang('ssl_cert_not_found', 'http_error', [$cert]));
  }

  /**
   * For CurlRequest
   *
   * @return HTTPException
   */
  public static function forInvalidSSLKey($key)
  {
    return new static(lang('invalid_ssl_key', 'http_error', [$key]));
  }

  /**
   * For CurlRequest
   *
   * @return HTTPException
   *
   * @codeCoverageIgnore
   */
  public static function forCurlError($errorNum, $error)
  {
    return new static(lang('curl_error', 'http_error', [$errorNum, $error]));
  }

  /**
   * For URI
   *
   * @return HTTPException
   */
  public static function forUnableToParseURI($uri)
  {
    return new static(lang('cannot_parse_URI', 'http_error', [$uri]));
  }

  /**
   * For URI
   *
   * @return HTTPException
   */
  public static function forURISegmentOutOfRange($segment)
  {
    return new static(lang('segment_out_of_range', 'http_error', [$segment]));
  }

  /**
   * For URI
   *
   * @return HTTPException
   */
  public static function forInvalidPort($port)
  {
    return new static(lang('invalid_port', 'http_error', [$port]));
  }

  /**
   * For URI
   *
   * @return HTTPException
   */
  public static function forMalformedQueryString()
  {
    return new static(lang('malformed_query_string', 'http_error'));
  }

  /**
   * For URI
   *
   * @return HTTPException
   */
  public static function forURISegmentNotExistsNumber($number)
  {
    return new static(lang('segment_number_does_not_exist', 'http_error', [$number]));
  }

  /**
   * For Message
   *
   * @return HTTPException
   */
  public static function forInvalidHTTPProtocol($protocols)
  {
    return new static(lang('invalid_HTTP_protocol', 'http_error', [$protocols]));
  }

  /**
   * For IncomingRequest
   *
   * @return HTTPException
   */
  public static function forInvalidNegotiationType($type)
  {
    return new static(lang('invalid_negotiation_type', 'http_error', [$type]));
  }


  /**
   * For Negotiate
   *
   * @return HTTPException
   */
  public static function forEmptySupportedNegotiations()
  {
    return new static(lang('empty_supported_negotiations', 'http_error'));
  }

  /**
   * For RedirectResponse
   *
   * @return HTTPException
   */
  public static function forInvalidRedirectRoute($route)
  {
    return new static(lang('invalid_route', 'http_error', [$route]));
  }

  /**
   * For Response
   *
   * @return HTTPException
   */
  public static function forMissingResponseStatus()
  {
    return new static(lang('missing_response_status', 'http_error'));
  }

  /**
   * For Response
   *
   * @return HTTPException
   */
  public static function forInvalidStatusCode($code)
  {
    return new static(lang('invalid_status_code', 'http_error', [$code]));
  }

  /**
   * For Response
   *
   * @return HTTPException
   */
  public static function forUnkownStatusCode($code)
  {
    return new static(lang('unknown_status_code', 'http_error', [$code]));
  }
  /**
   * For Uploaded file move
   *
   * @return HTTPException
   */
  public static function forAlreadyMoved()
  {
    return new static(lang('already_moved', 'http_error'));
  }

  /**
   * For Uploaded file move
   *
   * @return HTTPException
   */
  public static function forInvalidFile($path = null)
  {
    return new static(lang('invalid_file', 'http_error'));
  }

  /**
   * For Uploaded file move
   *
   * @return HTTPException
   */
  public static function forMoveFailed($source, $target, $error)
  {
    return new static(lang('move_failed', 'http_error', [$source, $target, $error]));
  }

  /**
   * For Invalid SameSite attribute setting
   *
   * @return HTTPException
   *
   * @deprecated Use `CookieException::forInvalidSameSite()` instead.
   *
   * @codeCoverageIgnore
   */
  public static function forInvalidSameSiteSetting($samesite)
  {
    return new static(lang('invalid_same_site_setting', 'http_error', [$samesite]));
  }


}
