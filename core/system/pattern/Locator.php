<?php

namespace AC\core\system\pattern;

class Locator
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
    if (!isset(self::$instances[$key])) {
      // Make sure $getShared is false
      $params[] = false;
      
      self::$instances[$key] = static::$key(...$params);
    }

    return self::$instances[$key];
  }
  
  protected static function getSharedInstanceItem($key, $keyItem, ...$params)
  {
    $key = strtolower($key);
    if (!isset(self::$instances[$key][$keyItem])) {
      // Make sure $getShared is false
      $params[] = false;
      
      self::$instances[$key][$keyItem] = static::$key($keyItem, ...$params);
    }
    
    return self::$instances[$key][$keyItem];
  }
  
  /**
   * Provides the ability to perform case-insensitive calling of service
   * names.
   *
   * @return mixed
   */
  public static function __callStatic($name, array $arguments)
  {
    $service = self::exists($name);
    
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
    $services = [self::class];
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
  public static function reset(): void
  {
    self::$instances = [];
  }
  /**
   * Resets shared instances for a single service.
   */
  public static function resetSingle($name): void
  {
    unset(self::$instances[$name]);
  }
}