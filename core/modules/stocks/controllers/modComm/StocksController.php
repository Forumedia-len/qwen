<?php

namespace AC\core\modules\stocks\controllers\modComm;

use AC\core\engines\StocksEngine;
use AC\core\modules\stocks\engines\StocksGroupsEngine;
use AC\core\modules\stocks\entities\entity\StocksGroup;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class StocksController extends ModCommController
{
  public function getPreferencesForGroupConditions(ModCommRequest $request): ModCommResponse
  {
    /**
     * @var StocksGroup $group
     */
    if (($stock = $this->stocksEngine()?->getStockAsDto($request->getDataValue('stockId')))
      && $stock->group && ($group = $stock->conditions[$stock->group]) && $group->active) {
      
      return ModCommHelper::success(['data' => $group->getPreference($request->getDataValue('preferenceId'))]);
    }
    
    return ModCommHelper::error();
  }
  
  public function getStocks(ModCommRequest $request): ModCommResponse
  {
    $stocks = $this->stocksEngine()->getStocksAs($request->getDataValue('as', 'array'));
    
    return ModCommHelper::success(['stocks' => $stocks]);
  }
  
  private function stocksEngine(): StocksEngine
  {
    return getEngine('stocks', false);
  }
  
  private function stocksGroupsEngine(): StocksGroupsEngine
  {
    return getEngine('stocksGroups', false);
  }
}