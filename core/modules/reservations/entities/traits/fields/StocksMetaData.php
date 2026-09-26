<?php

namespace AC\core\modules\reservations\entities\traits\fields;

use AC\app\entities\traits\fields\HasMetaData;
use AC\core\modules\reservations\entities\dto\ReservationStockDto;
use AC\core\modules\reservations\locators\ReservServiceLocator;

trait StocksMetaData
{
  use HasMetaData;
  
  public function setStock($stock): void
  {
    $stocks = $this->getStocks();
    if ($stock instanceof ReservationStockDto
      || (is_numeric($stock) && $stock = ReservServiceLocator::priceOptions()->stock($stock))
    ) {
      $stocks[$stock->getId()] = $stock;
    }
    
    $this->setData('stocks', $stocks);
  }
  
  public function getStock($stockId): ?ReservationStockDto
  {
    return $this->getStocks()[$stockId] ?? null;
  }
  
  /**
   * @return array<ReservationStockDto>
   */
  public function getStocks(): array
  {
    return $this->getData('stocks');
  }
}