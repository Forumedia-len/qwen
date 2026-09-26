<?php

namespace AC\core\system\http\request;

/**
 * Expected behavior of an HTTP request
 *
 * @mixin IncomingRequest
 * @mixin CURLRequest
 */
interface RequestInterface
{
  /**
   * Gets the user's IP address.
   * Supplied by RequestTrait.
   *
   * @return string IP address
   */
  public function getIPAddress();

  /**
   * Validate an IP address
   *
   * @param string $ip    IP Address
   * @param string $which IP protocol: 'ipv4' or 'ipv6'
   *
   * @deprecated Use Validation instead
   */
  public function isValidIP($ip, $which = null);

  /**
   * Get the request method.
   * An extension of PSR-7's getMethod to allow casing.
   *
   * @param bool $upper Whether to return in upper or lower case.
   *
   * @deprecated The $upper functionality will be removed and this will revert to its PSR-7 equivalent
   */
  public function getMethod($upper = false);

  /**
   * Fetch an item from the $_SERVER array.
   * Supplied by RequestTrait.
   *
   * @param string $index  Index for item to be fetched from $_SERVER
   * @param null   $filter A filter name to be applied
   *
   * @return mixed
   */
  public function getServer($index = null, $filter = null);
}
