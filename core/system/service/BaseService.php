<?php

namespace AC\core\system\service;

use AC\app\config\AppConfig;
use AC\app\config\FormatConfig;
use AC\app\config\LangConfig;
use AC\core\system\App;
use AC\core\system\autoloader\Autoloader;
use AC\core\system\autoloader\FileLocator;
use AC\core\system\db\Query;
use AC\core\system\debug\Exceptions;
use AC\core\system\format\Format;
use AC\core\system\http\Header;
use AC\core\system\http\Negotiate;
use AC\core\system\http\request\CURLRequest;
use AC\core\system\http\request\IncomingRequest;

use AC\core\system\http\request\Request;
use AC\core\system\http\request\RequestInterface;
use AC\core\system\http\response\Response;
use AC\core\system\http\response\ResponseInterface;
use AC\core\system\http\url\URI;
use AC\core\system\http\url\Url;
use AC\core\system\language\Translations;
use AC\core\system\pattern\Locator;
use AC\core\system\router\RouteCollection;
use AC\core\system\router\RouteCollectionInterface;
use AC\core\system\router\Router;
use AC\core\system\session\Session;
use AC\core\system\structure\Structure;
use AC\core\system\view\View;

/**
 *
 */
class BaseService extends Locator
{
  /**
   * Cache for instance of any services that
   * have been requested as a "shared" instance.
   * Keys should be lowercase service names.
   *
   * @var array
   */
  protected static array $instances = [];


  /**
   * Returns a shared instance of any of the class' services.
   *
   * $key must be a name matching a service.
   *
   * @param mixed ...$params
   *
   * @return mixed
   */
  protected static function getSharedInstance($key, ...$params)
  {
    $key = strtolower($key);

    if (!isset(static::$instances[$key])) {
      // Make sure $getShared is false
      $params[] = false;

      static::$instances[$key] = Services::$key(...$params);
    }

    return static::$instances[$key];
  }

  protected static function getSharedInstanceItem($key, $keyItem, ...$params)
  {
    $key = strtolower($key);
    if (!isset(static::$instances[$key][$keyItem])) {
      // Make sure $getShared is false
      $params[] = false;

      static::$instances[$key][$keyItem] = Services::$key($keyItem, ...$params);
    }

    return static::$instances[$key][$keyItem];
  }

  /**
   * The Autoloader class is the central class that handles our
   * spl_autoload_register method, and helper methods.
   *
   * @return Autoloader
   */
  public static function autoloader($getShared = true)
  {

    if ($getShared) {
      if (empty(static::$instances['autoloader'])) {
        static::$instances['autoloader'] = new Autoloader();
      }

      return static::$instances['autoloader'];
    }

    return new Autoloader();
  }

  /**
   *
   * @return App
   */
  public static function app($getShared = true)
  {
    /** @var $appClassName App */
    $appClassName = useClass(paths()->systemDir . 'App');
    if ($getShared) {
      if (empty(static::$instances['app'])) {
        static::$instances['app'] = $appClassName::initialize();
      }

      return static::$instances['app'];
    }

    return $appClassName::initialize(true);
  }

  /**
   * The file locator provides utility methods for looking for non-classes
   * within namespaced folders, as well as convenience methods for
   * loading 'helpers', and 'libraries'.
   *
   * @return FileLocator
   */
  public static function locator($getShared = true)
  {
    if ($getShared) {
      if (empty(static::$instances['locator'])) {
        static::$instances['locator'] = new FileLocator(static::autoloader());
      }

      return static::$instances['locator'];
    }

    return new FileLocator(static::autoloader());
  }


  /**
   * Provides the ability to perform case-insensitive calling of service
   * names.
   *
   * @return mixed
   */
  public static function __callStatic($name, array $arguments)
  {
    $service = static::exists($name);

    if ($service === null) {
      return null;
    }

    return $service::$name(...$arguments);
  }

  /**
   * Check if the requested service is defined and return the declaring
   * class. Return null if not found.
   */
  public static function exists($name): ?string
  {
    $services = [Services::class];
    $name     = strtolower($name);

    foreach ($services as $service) {
      if (method_exists($service, $name)) {
        return $service;
      }
    }

    return null;
  }

  /**
   * Reset shared instances .
   */
  public static function reset($initAutoloader = false): void
  {
    static::$instances = [];

    if ($initAutoloader) {
      static::autoloader();
    }
  }

  /**
   * Resets shared instances for a single service.
   */
  public static function resetSingle($name): void
  {
    unset(static::$instances[$name]);
  }
}
