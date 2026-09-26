<?php

namespace AC\core\modules\config\controllers\modComm;

use AC\core\engines\ExtraEngine;
use AC\core\modules\config\entities\dto\ExtraDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class ExtraController extends ModCommController
{
  public function getExtra(ModCommRequest $request): ModCommResponse
  {
    $params = array_merge([
      'as'  => 'dto',
      'dtoClass' => ExtraDto::class,
    ], $request->getDataValue('params', []));
    return ModCommResponse::success(['extra' => $this->engine()->getAllExtra($params)]);
  }
  
  private function engine(): ExtraEngine
  {
    return getEngine('extra', false);
  }
}