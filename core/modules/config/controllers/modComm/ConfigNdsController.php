<?php

namespace AC\core\modules\config\controllers\modComm;

use AC\core\engines\NdsEngine;
use AC\core\modules\config\entities\dto\NdsDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class ConfigNdsController extends ModCommController
{
  public function getNds(ModCommRequest $request): ModCommResponse
  {
    $params = array_merge(['key' => 'nds_id', 'as' => 'dto', 'dtoClass' => NdsDto::class, 'addDefault' => false], $request->getData());
    if ($this->getEngine()->getAllNds($nds, [] ,[], $params)) {
      return ModCommHelper::success(['nds' => $nds]);
    }
    return ModCommHelper::error();
  }
  
  protected function getEngine(): NdsEngine
  {
    return getEngine('nds', false);
  }
}