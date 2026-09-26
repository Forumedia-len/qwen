<?php

namespace AC\core\system\cookie;

use DateTimeInterface;

/**
 * Interface for a fresh Cookie instance with selected attribute(s)
 * only changed from the original instance.
 */
interface CloneableCookieInterface extends CookieInterface
{
  /**
   * Creates a new Cookie with a new cookie prefix.
   *
   * @return static
   */
  public function withPrefix($prefix = '');

  /**
   * Creates a new Cookie with a new name.
   *
   * @return static
   */
  public function withName($name);

  /**
   * Creates a new Cookie with new value.
   *
   * @return static
   */
  public function withValue($value);

  /**
   * Creates a new Cookie with a new cookie expires time.
   *
   * @param DateTimeInterface|int|string $expires
   *
   * @return static
   */
  public function withExpires($expires);

  /**
   * Creates a new Cookie that will expire the cookie from the browser.
   *
   * @return static
   */
  public function withExpired();

  /**
   * Creates a new Cookie that will virtually never expire from the browser.
   *
   * @return static
   */
  public function withNeverExpiring();

  /**
   * Creates a new Cookie with a new path on the server the cookie is available.
   *
   * @return static
   */
  public function withPath($path);

  /**
   * Creates a new Cookie with a new domain the cookie is available.
   *
   * @return static
   */
  public function withDomain($domain);

  /**
   * Creates a new Cookie with a new "Secure" attribute.
   *
   * @return static
   */
  public function withSecure($secure = true);

  /**
   * Creates a new Cookie with a new "HttpOnly" attribute
   *
   * @return static
   */
  public function withHTTPOnly($httponly = true);

  /**
   * Creates a new Cookie with a new "SameSite" attribute.
   *
   * @return static
   */
  public function withSameSite($samesite);

  /**
   * Creates a new Cookie with URL encoding option updated.
   *
   * @return static
   */
  public function withRaw($raw = true);
}
