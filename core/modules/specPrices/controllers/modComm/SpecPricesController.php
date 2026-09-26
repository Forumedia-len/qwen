<?php

namespace AC\core\modules\specPrices\controllers\modComm;

use AC\core\modules\specPrices\engines\SpecPricesEngine;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class SpecPricesController extends ModCommController
{
  public function getSpecPrices(ModCommRequest $request): ModCommResponse
  {
    $specPrices = $this->specPriceEngine()->getSpecPricesAs($request->getDataValue('as', 'array'));
    
    return ModCommHelper::success(['specPrices' => $specPrices]);
  }
  
  private function specPriceEngine(): SpecPricesEngine
  {
    return getEngine('SpecPrices', false);
  }
}