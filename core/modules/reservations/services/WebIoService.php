<?php

namespace AC\core\modules\reservations\services;

use AC\core\system\modules\modComm\helpers\ModCommHelper;

class WebIoService
{
  public function getWebIoTypes(): array
  {
    static $webIoTypes;
    if (empty($webIoTypes)) {
      $webIoTypes = ModCommHelper::get('WebIo', 'WebIo/getWebIoTypes', [], 'webIoTypes', []);
    }
    return $webIoTypes;
  }
}