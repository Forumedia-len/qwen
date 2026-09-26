<?php

namespace AC\core\modules\webIo\controllers\modComm;

use AC\core\modules\webIo\entities\dto\WebIoTypeSateDto;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;
use AC\core\system\helpers\DataHelper;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class WebIoController extends ModCommController
{
  public function getWebIoTypes(ModCommRequest $request): ModCommResponse
  {
    return ModCommHelper::success(['webIoTypes' => WebIoModel::getWebIoTypes()]);
  }
  
  public function getWebIoActiveTypes(ModCommRequest $request): ModCommResponse
  {
    $params = array_merge([
      'asKey'                 => 'alias',
      'onlyActive'            => true,
      'all'                   => false,
      'onlyUseForReservation' => true,
      'as'                    => 'dto',
      'dtoClass'              => WebIoTypeSateDto::class,
      'key'                   => 'alias',
    ], $request->getData());
    
    return ModCommHelper::success([
      'webIoTypes' => DataHelper::getDataAs(WebIoTypeSatesModel::getTypeSates(
        $params['asKey'],
        $params['onlyActive'],
        $params['all'],
        $params['onlyUseForReservation']), $params),
      []
    ]);
  }
}