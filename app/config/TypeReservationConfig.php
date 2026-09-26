<?php

namespace AC\app\config;

use AC\core\system\config\BaseConfig;

class TypeReservationConfig extends BaseConfig
{
  public function useOtherGroupTypes(): bool
  {
    return !defined('USE_FITNESS_ABO') || USE_FITNESS_ABO;
  }
}