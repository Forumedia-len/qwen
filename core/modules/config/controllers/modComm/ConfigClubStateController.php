<?php

namespace AC\core\modules\config\controllers\modComm;

use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\system\modules\modComm\controllers\ModCommController;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\core\system\modules\modComm\http\ModCommRequest;
use AC\core\system\modules\modComm\http\ModCommResponse;

class ConfigClubStateController extends ModCommController
{
  public function getModel(ModCommRequest $request): ModCommResponse
  {
    return ModCommHelper::success(['model' => $this->model()]);
  }
  protected function model(): ConfigClubStateModel
  {
    return new ConfigClubStateModel();
  }
}