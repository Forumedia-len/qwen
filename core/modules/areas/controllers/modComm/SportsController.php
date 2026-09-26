<?php

namespace AC\core\modules\areas\controllers\modComm;

use AC\core\engines\SportsEngine;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class SportsController extends ModCommController
{
  public function hasColor(ModCommRequest $request): ModCommResponse
  {
    return ModCommHelper::success(['has' => $this->getSportsEngine()->hasColor()]);
  }
  
  protected function getSportsEngine(): SportsEngine
  {
    return getEngine('sports');
  }
}