<?php

namespace AC\app\services;

use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\modules\config\entities\dto\NdsDto;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\discounts\entities\dto\DiscountDto;
use AC\core\modules\users\entities\dto\UserDto;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\pattern\Locator;

class DataService extends Locator
{
  /** Получить все площадки
   *
   * @param array $params
   * @param bool  $getShared
   * @return array<AreaDto>
   */
  public static function areas(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('areas', $params, false);
    }
    return ModCommHelper::get('areas', 'getAreas', $params, 'areas', []);
  }
  
  /** Получить спорт_тип данные
   *
   * @param array $params
   * @param bool  $getShared
   * @return array
   */
  public static function sportsByType(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('sportsByType', $params, false);
    }
    return ModCommHelper::get('areas', 'relevantSportsByType', $params, 'sportsByType', []);
  }
  
  /** Получить данные по опциям
   * @param array $params
   * @param bool  $getShared
   * @return array
   */
  public static function stocks(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('stocks', $params, false);
    }
    return ModCommHelper::get('stocks', 'getStocks', array_merge(['as' => 'dto'], $params), 'stocks', []);
  }
  
  /** Получить данные по спец ценам
   * @param array $params
   * @param bool  $getShared
   * @return array
   */
  public static function specPrices(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('specPrices', $params, false);
    }
    return ModCommHelper::get('specPrices', 'getSpecPrices', array_merge(['as' => 'dto'], $params), 'specPrices', []);
  }
  
  /**
   * @param array $params
   * @param bool  $getShared
   * @return array<NdsDto>
   */
  public static function nds(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('nds', $params, false);
    }
    return ModCommHelper::get('config', 'configNds/getNds', $params, 'nds', []);
  }
  
  public static function clubStateModel(bool $getShared = true): ConfigClubStateModel
  {
    if ($getShared) {
      return self::getSharedInstance('clubStateModel', false);
    }
    return ModCommHelper::get('config', 'configClubState/getClubStateModel', [], 'clubStateModel', new ConfigClubStateModel());
  }
  
  public static function webIoActiveTypes(bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('webIoActiveTypes', false);
    }
    return ModCommHelper::get('WebIo', 'getWebIoActiveTypes', [], 'webIoTypes', []);
  }
  
  /**
   * @param array $params
   * @param bool  $getShared
   * @return array<DiscountDto>
   */
  public static function discounts(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('discounts', $params, false);
    }
    return ModCommHelper::get('discounts', 'getDiscounts', $params, 'discounts', []);
  }
  
  public static function extra(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('extra', $params, false);
    }
    return ModCommHelper::get('config', 'extra/getExtra', $params, 'extra', []);
  }
  
  /**
   * Получить всех пользователей
   * @param array $params
   * @param bool  $getShared
   * @return array<UserDto>
   */
  public static function users(array $params = [], bool $getShared = true): array
  {
    if ($getShared) {
      return self::getSharedInstance('users', $params, false);
    }
    return ModCommHelper::get('users', 'getAllUsers', $params, 'users', []);
  }
}