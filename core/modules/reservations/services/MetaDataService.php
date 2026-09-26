<?php

namespace AC\core\modules\reservations\services;

use AC\core\modules\reservations\entities\dto\ReservationDataDto;
use AC\core\modules\reservations\entities\dto\ReservationStockDto;

class MetaDataService
{
  /**
   * @param ReservationStockDto $stock
   * @return array<ReservationDataDto>
   */
  public function stocks(ReservationStockDto $stock): array
  {
    $out = [];
    foreach (['stock_id', 'rate', 'dimension'] as $key) {
      $value = in_array($key, ['id', 'stock_id'], true) ? $stock->getId() : $stock->$key;
      $out[] = $this->setMeta($key, $value, 'stocks', $stock->getId());
    }
    
    return $out;
  }
  
  public function setMeta($name, $value, $typeBlock = null, $entryId = null): ReservationDataDto
  {
    return ReservationDataDto::fromArray([
      'type_block' => $typeBlock,
      'entry_id'   => $entryId,
      'name'       => $name,
      'value'      => $value,
    ]);
  }
}