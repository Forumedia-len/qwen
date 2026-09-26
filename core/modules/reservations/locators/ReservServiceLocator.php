<?php

namespace AC\core\modules\reservations\locators;

use AC\core\modules\reservations\entities\dto\ReservationAreaDto;
use AC\core\modules\reservations\entities\dto\ReservationClientDto;
use AC\core\modules\reservations\services\MetaDataService;
use AC\core\modules\reservations\services\PriceOptionsService;
use AC\core\modules\reservations\services\SumPriceService;
use AC\core\modules\reservations\services\WebIoService;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\pattern\Locator;

class ReservServiceLocator extends Locator
{
  private static string $moduleName = 'reservations';
  
  /**
   * Сервис для работы с опциями цены
   *  опции, спец цены и т.д.
   *
   * @param bool $getShared
   * @return PriceOptionsService
   */
  public static function priceOptions(bool $getShared = true): PriceOptionsService
  {
    if ($getShared) {
      return self::getSharedInstance('priceOptions');
    }
    
    return useClass(self::getModuleNamespace() . '\\services\\PriceOptionsService', true);
  }
  
  /**
   * Сервис для формирования итоговой суммы бронирования
   * вычисляем скидки, наценки, опции и т.д.
   * @param bool $getShared
   * @return SumPriceService
   */
  public static function sumPrice(bool $getShared = true): SumPriceService
  {
    if ($getShared) {
      return self::getSharedInstance('sumPrice');
    }
    
    return useClass(self::getModuleNamespace() . '\\services\\SumPriceService', true);
  }
  
  /**
   * Сервис для работы с webIo - свет, тепло и т.д.
   * @param bool $getShared
   * @return WebIoService
   */
  public static function webIo(bool $getShared = true): WebIoService
  {
    if ($getShared) {
      return self::getSharedInstance('webIo');
    }
    
    return useClass(self::getModuleNamespace() . '\\services\\WebIoService', true);
  }
  
  /**
   * Сервис для работы с метаданными бронирования
   * создает дополнительные данные для бронирования
   * например используем для сохранения нескольких опций
   *
   * @param bool $getShared
   * @return MetaDataService
   */
  public static function metaData(bool $getShared = true): MetaDataService
  {
    if ($getShared) {
      return self::getSharedInstance('metaData');
    }
    
    return useClass(self::getModuleNamespace() . '\\services\\MetaDataService', true);
  }
  
  /**
   * Получение данных клиента бронирования
   *
   * @param $id
   * @return ReservationClientDto - возвращаем dto для удобства
   */
  public static function client($id): ReservationClientDto
  {
    $client = ModCommHelper::get('clients', 'clients/getClientDataById', ['client_id' => $id], 'client', []);
    return ReservationClientDto::fromArray(is_object($client) ? $client->toArray() : $client);
  }
  
  /**
   * Получение данных о площадке бронирования
   * @param $id
   * @return ReservationAreaDto
   */
  public static function area($id): ReservationAreaDto
  {
    $area = ModCommHelper::get('areas', 'areas/getAreaDataById', ['area_id' => $id], 'area', []);
    
    return ReservationAreaDto::fromArray(is_object($area) ? $area->toArray() : $area);
  }
  
  private static function getModuleNamespace(): string
  {
    return paths()::MODULES_DIR . '\\' . self::$moduleName;
  }
}