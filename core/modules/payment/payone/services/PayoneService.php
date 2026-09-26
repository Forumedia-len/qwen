<?php

namespace AC\core\modules\payment\payone\services;

use AC\core\modules\payment\payone\http\response\PayoneResponse;
use AC\core\modules\payment\payone\session\PayoneSession;
use AC\core\system\pattern\Locator;

class PayoneService extends Locator
{
  public const REDIS_PARAMS_TTL = 3600;

  public static function session(?string $sessionKey = null, $getShared = true): PayoneSession
  {
    if ($getShared) {
      return static::getSharedInstance('session', $sessionKey);
    }

    return useClass(paths()->getModuleDir() . 'payment\payone\session\PayoneSession', true, $sessionKey);
  }

  public static function response(?string $sessionKey = null, $getShared = true): PayoneResponse
  {
    if ($getShared) {
      return static::getSharedInstance('response', $sessionKey);
    }

    return useClass(paths()->getModuleDir() . 'payment\payone\http\response\PayoneResponse', true, $sessionKey);
  }

  protected static function getSharedInstance($key, ...$params)
  {
    $instanceKey = 'PayoneService|' . $key;
    if (!isset(static::$instances[$instanceKey])) {
      $params[] = false;

      static::$instances[$instanceKey] = self::$key(...$params);
    }

    return static::$instances[$instanceKey];
  }
}