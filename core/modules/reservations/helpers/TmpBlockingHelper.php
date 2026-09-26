<?php

namespace AC\core\modules\reservations\helpers;

use AC\core\system\helpers\RedisHelper;

class TmpBlockingHelper
{
  private static function generateKey(int $areaId, ?string $date = null, ?string $time = null): string
  {
    return rtrim("tmp_blocking:{$areaId}:" . ($date ?? '') . ":" . ($time ?? ''), ':');
  }

  public static function checkOrderBlock(int $areaId, string $date, ?array $times = [], ?string $checkClientId = null): bool
  {
    if (!empty($times)) {
      foreach ($times as $time) {
        if (!self::checkOrderBlockByTime($areaId, $date, $time, $checkClientId)) {
          return false;
        }
      }
    }

    return true;
  }

  public static function checkOrderBlockByTime(int $areaId, string $date, string $time, ?string $checkClientId = null): bool
  {
    $key  = self::generateKey($areaId, $date, $time);
    $data = RedisHelper::get($key);
    if (is_array($data)) {
      if (!$checkClientId || (isset($data['client_id']) && $data['client_id'] != $checkClientId)) {
        return false;
      }
    }

    return true;
  }

  public static function setOrderBlock(int $areaId, string $date, ?array $times, array $data = []): void
  {
    if (!empty($times)) {
      foreach ($times as $time) {
        RedisHelper::set(self::generateKey($areaId, $date, $time), $data,
          config('payment')->orderBlockMinute() * 60);
      }
    }
  }

  public static function getOrderBlock(int $areaId, string $date, ?string $checkClientId = null): array
  {
    $times   = [];
    $baseKey = self::generateKey($areaId, $date);
    foreach (RedisHelper::scanKeys($baseKey . ':*') as $key) {
      $time = str_replace($baseKey . ':', '', $key);
      if (!self::checkOrderBlockByTime($areaId, $date, $time, $checkClientId)) {
        $times[] = $time;
      }
    }
    asort($times);

    return $times;
  }

  public static function deleteOrderBlock(int $areaId, string $date, ?array $times, string $clientId): void
  {
    if (!empty($times)) {
      foreach ($times as $time) {
        self::deleteOrderBlockByTime($areaId, $date, $time, $clientId);
      }
    }
  }

  public static function deleteOrderBlockByTime(int $areaId, string $date, string $time, string $clientId): void
  {
    $key  = self::generateKey($areaId, $date, $time);
    $data = RedisHelper::get($key);
    if (is_array($data) && isset($data['client_id']) && $data['client_id'] == $clientId) {
      RedisHelper::delete($key);
    }
  }
}