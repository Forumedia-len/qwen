<?php

namespace AC\core\modules\reservations\entities\dto;

use AC\app\entities\traits\sets\HasClient;
use AC\core\system\entities\dto\DtoInterface;

class ReservationClientDto implements DtoInterface
{
  use HasClient;
}