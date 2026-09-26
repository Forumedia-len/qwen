<?php

namespace AC\core\system\http\message;


use AC\core\system\exceptions\http\HTTPException;
use AC\core\system\http\Header;

/**
 * Expected behavior of an HTTP request
 */
interface MessageInterface
{
  /**
   * Sets the body of the current message.
   *
   * @param mixed $data
   *
   * @return $this
   */
  public function setBody($data);

  /**
   * Appends data to the body of the current message.
   *
   * @param mixed $data
   *
   * @return $this
   */
  public function appendBody($data);

  /**
   * Populates the $headers array with any headers the getServer knows about.
   */
  public function populateHeaders();

  /**
   * Returns an array containing all Headers.
   *
   * @return array<string, Header> An array of the Header objects
   */
  public function headers();

  /**
   * Returns a single Header object. If multiple headers with the same
   * name exist, then will return an array of header objects.
   *
   * @param string $name
   *
   * @return array|Header|null
   */
  public function header($name);

  /**
   * Sets a header and it's value.
   *
   * @param array|string|null $value
   *
   * @return $this
   */
  public function setHeader($name, $value);

  /**
   * Removes a header from the list of headers we track.
   *
   * @return $this
   */
  public function removeHeader($name);

  /**
   * Adds an additional header value to any headers that accept
   * multiple values (i.e. are an array or implement ArrayAccess)
   *
   * @return $this
   */
  public function appendHeader($name, $value);

  /**
   * Adds an additional header value to any headers that accept
   * multiple values (i.e. are an array or implement ArrayAccess)
   *
   * @return $this
   */
  public function prependHeader($name, $value);

  /**
   * Sets the HTTP protocol version.
   *
   * @return $this
   * @throws HTTPException For invalid protocols
   *
   */
  public function setProtocolVersion($version);
}
