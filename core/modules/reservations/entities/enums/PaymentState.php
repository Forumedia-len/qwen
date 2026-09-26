<?php

namespace AC\core\modules\reservations\entities\enums;

enum PaymentState: string
{
  case Paid    = '1';
  case NotPaid = '0';
}