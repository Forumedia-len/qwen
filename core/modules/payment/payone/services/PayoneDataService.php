<?php

namespace AC\core\modules\payment\payone\services;

use AC\core\modules\payment\payone\entities\dto\PortalDataDto;
use AC\core\modules\payment\payone\entities\dto\PersonalDataDto;
use AC\core\modules\payment\payone\entities\dto\OrderDataDto;
use AC\core\modules\payment\payone\entities\dto\UrlDataDto;
use AC\core\system\pattern\Locator;

/**
 * Сервис для создания и управления DTO объектами Payone платежей
 */
class PayoneDataService extends Locator
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
   * Создает и возвращает экземпляр PortalDataDto
   *
   * @param array $data Данные для инициализации DTO
   * @param bool  $getShared
   *
   * @return PortalDataDto
   */
  public static function portal(array $data = [], bool $getShared = true): PortalDataDto
  {
    if ($getShared) {
      return static::getSharedInstance('portal', $data);
    }

    return PortalDataDto::fromArray($data);
  }

  /**
   * Создает и возвращает экземпляр PersonalDataDto
   *
   * @param array $data Данные для инициализации DTO
   * @param bool  $getShared
   *
   * @return PersonalDataDto
   */
  public static function personal(array $data = [], bool $getShared = true): PersonalDataDto
  {
    if ($getShared) {
      return static::getSharedInstance('personal', $data);
    }

    return PersonalDataDto::fromArray($data);
  }

  /**
   * Создает и возвращает экземпляр OrderDataDto
   *
   * @param array $data Данные для инициализации DTO
   * @param bool  $getShared
   *
   * @return OrderDataDto
   */
  public static function order(array $data = [], bool $getShared = true): OrderDataDto
  {
    if ($getShared) {
      return static::getSharedInstance('order', $data);
    }

    return OrderDataDto::fromArray($data);
  }

  /**
   * Создает и возвращает экземпляр UrlDataDto
   *
   * @param array $data Данные для инициализации DTO
   * @param bool  $getShared
   *
   * @return UrlDataDto
   */
  public static function url(array $data = [], bool $getShared = true): UrlDataDto
  {
    if ($getShared) {
      return static::getSharedInstance('url', $data);
    }

    return UrlDataDto::fromArray($data);
  }

  /**
   * Создает все необходимые DTO объекты для Payone платежа
   *
   * @param array $data Общие данные для всех DTO
   *
   * @return array Массив DTO объектов
   */
  public static function allInstance(array $data = []): array
  {
    return [
      'portalData'   => self::portal($data),
      'personalData' => self::personal($data),
      'orderData'    => self::order($data),
      'urlData'      => self::url($data),
    ];
  }

  protected static function getSharedInstance($key, ...$params)
  {
    $key = strtolower($key);
    if (!isset(static::$instances[$key]) || !empty($params[0])) {
      $params[] = false;

      static::$instances[$key] = self::$key(...$params);
    }

    return static::$instances[$key];
  }
}
