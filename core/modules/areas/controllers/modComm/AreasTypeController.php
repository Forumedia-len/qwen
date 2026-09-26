<?php

namespace AC\core\modules\areas\controllers\modComm;

use AC\core\engines\AreasEngine;
use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class AreasTypeController extends ModCommController
{
  public function getAreaType(ModCommRequest $request): ModCommResponse
  {
    if ($data = $this->getTypeData($request->getDataValue('type_id'))) {
      return ModCommHelper::success(['data' => $request->getDataValue('asDto', false) ? $data['dto'] : $data['type']]);
    }
    
    return ModCommHelper::error('Area type not found');
  }
  
  public function selectActiveTypes(ModCommRequest $request): ModCommResponse
  {
    $key = $request->getDataValue('key', 'type_id');
    
    return ModCommHelper::success(['data' => $this->areasEngine()?->selectActiveType($key)]);
  }
  
  public function getCurrentAliasById(ModCommRequest $request): ModCommResponse
  {
    if ($data = $this->getTypeData($request->getDataValue('type_id'))) {
      return ModCommHelper::success(['currentAlias' => $data['dto']->getCurrentAlias()]);
    }
    
    return ModCommHelper::error('Area type not found');
  }
  
  private function areasEngine(): AreasEngine
  {
    return getEngine('areas', false);
  }
  
  private function getTypeData($type_id): array
  {
    if ($this->areasEngine()->getTypeData($type_id, $area_type_data)) {
      return ['type' => $area_type_data, 'dto' => AreaTypeDto::fromArray($area_type_data)];
    }
    return [];
  }
  
}