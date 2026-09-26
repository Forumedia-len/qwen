<?php

namespace AC\core\engines;

use AC\core\system\autoloader\Autoloader;
use AC\core\system\engine\BaseEngine;

class ClubStateEngine extends BaseEngine
{
  /** Возвращает название таблицы без префикса
   * @return string
   */
  public function tableName()
  {
    return 'config_club_state';
  }

  public function modelName($modelName = false)
  {
    return parent::modelName(useClass('core\modules\config\models\ConfigClubStateModel'));
  }
}