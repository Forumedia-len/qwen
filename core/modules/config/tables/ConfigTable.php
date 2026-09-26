<?php

namespace AC\core\modules\config\tables;

use AC\core\system\db\Query;
use AC\core\system\model\BaseModel;

abstract class ConfigTable extends BaseModel
{

  public $type;
  public $alias;
  public $value;
  public $default;
  public $comment;

  public $tableName = 'config';

  public static function removeByTypeAndAlias($type, $alias)
  {
    Query::sqlQuery(
      'delete from ' . Query::tableName('config') . ' where type=:type and alias=:alias',
      [':type' => $type, ':alias' => $alias],
      false
    );
  }

}