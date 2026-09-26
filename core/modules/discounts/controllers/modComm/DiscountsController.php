<?php

namespace AC\core\modules\discounts\controllers\modComm;

use AC\core\engines\DiscountsEngine;
use AC\core\modules\discounts\entities\dto\DiscountDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class DiscountsController extends ModCommController
{
  public function getDiscounts(ModCommRequest $request): ModCommResponse
  {
    $params = array_merge([
      'key'      => 'discount_id',
      'as'       => 'dto',
      'dtoClass' => DiscountDto::class,
    ], $request->getDataValue('params', []));
    
    return ModCommHelper::success(['discounts' => $this->getEngine()->getAllDiscounts($params)]);
  }
  
  private function getEngine(): DiscountsEngine
  {
    return getEngine('discounts', false);
  }
}