<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\sets\HasStock;
use AC\core\modules\reservations\entities\contracts\Option;
use AC\core\system\entities\dto\DtoInterface;
use AC\core\system\helpers\NumberHelper;

class ReservationStockDto implements DtoInterface, Option
{
  use HasStock;
  
  public function amount(): string
  {
    return NumberHelper::format($this->rate, 2, '.', ' ') . ' ' .
      match ($this->dimension) {
        '2'     => '%',
        default => CURR_VALUTE
      };
  }
}