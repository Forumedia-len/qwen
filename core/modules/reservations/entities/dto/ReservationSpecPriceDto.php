<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\sets\HasSpecPrice;
use AC\core\modules\reservations\entities\contracts\Option;
use AC\core\system\entities\dto\DtoInterface;
use AC\core\system\helpers\NumberHelper;

class ReservationSpecPriceDto implements DtoInterface, Option
{
  use HasSpecPrice;
  
  public function amount(): string
  {
    return NumberHelper::format($this->rate, 2, '.', ' ') . ' ' . CURR_VALUTE;
  }
}