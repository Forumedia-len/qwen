<?php

namespace AC\core\modules\config\models;

class ConfigReservationModel extends ConfigModel
{

  /**
   * @return string[]
   */
  protected function getCurrentTypes(): array
  {
    return ['reservation', 'ticket'];
  }

}