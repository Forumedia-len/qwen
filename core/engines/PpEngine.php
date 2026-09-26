<?php

namespace AC\core\engines;

use AC\core\system\autoloader\Autoloader;
use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;

//проводимые акции
class PpEngine extends BaseEngine
{

  /**
   * {@inheritDoc}
   */
  public function tableName()
  {
    return 'config_pp';
  }

  public function modelName($modelName = false)
  {
    $modelName = useClass('core\modules\config\models\ConfigPPModel');

    return parent::modelName($modelName);
  }

  public function getData(&$result = [], $conditions = array(), $_order = array(), $params = [])
  {
    if($result = $this->all(array(), array('sort'))) {

      return true;
    }

    return false;
  }

  public function getPP($pp_id, &$result)
  {
    $query = 'select * from ' . Query::$prefix . $this->tableName() . ' where pp_id = :id';
    $result = Query::sqlQuery($query, array('id' => $pp_id), true);

    if(!empty($result)) {
      $result = $result[0];
      return true;
    }
    return false;
  }
}
