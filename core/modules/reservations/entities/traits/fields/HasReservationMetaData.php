<?php

namespace AC\core\modules\reservations\entities\traits\fields;

use AC\core\modules\reservations\entities\dto\ReservationDataDto;
use AC\core\modules\reservations\locators\ReservServiceLocator;
use AC\core\system\entities\dto\DtoInterface;

trait HasReservationMetaData
{
  use StocksMetaData;
  
  /**
   * todo возможно, стоит сделать дефолтное возвращаемое значение
   * сейчас возвращается если только есть такой метод
   * @return array<ReservationDataDto>
   */
  public function asArrayByReservationDataDto(): array
  {
    $metaDataService = ReservServiceLocator::metaData();
    $data            = [];
    foreach ($this->getKeys() as $key) {
      /** @var DtoInterface $item */
      foreach ($this->getData($key) as $item) {
        if (method_exists($metaDataService, $key)) {
          $data[] = $metaDataService->$key($item);
        }
      }
    }
    
    return array_merge(...$data);
  }
}