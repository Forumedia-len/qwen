<?php

namespace AC\core\modules\reservations\entities\enums;

enum Status: string
{
  case Complete   = '1';
  case Incomplete = '0';
}