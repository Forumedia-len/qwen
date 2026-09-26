<?php

namespace AC\core\system\exceptions;

/**
 * RouterException
 */
class RouterException extends BaseException
{
  /**
   * Thrown when the actual parameter type does not match
   * the expected types.
   *
   * @return RouterException
   */
  public static function forInvalidParameterType()
  {
    return new static(lang('invalidParameterType', 'router_error'));
  }

  /**
   * Thrown when a default route is not set.
   *
   * @return RouterException
   */
  public static function forMissingDefaultRoute()
  {
    return new static(lang('missingDefaultRoute', 'router_error'));
  }

  /**
   * Throw when controller or its method is not found.
   *
   * @return RouterException
   */
  public static function forControllerNotFound($controller, $method)
  {
    return new static(lang('controller_not_found', 'http_error', [$controller, $method]));
  }

  /**
   * Throw when route is not valid.
   *
   * @return RouterException
   */
  public static function forInvalidRoute($route)
  {
    return new static(lang('invalid_route', 'http_error', [$route]));
  }
}
