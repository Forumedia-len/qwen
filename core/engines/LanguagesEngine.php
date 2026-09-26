<?php

namespace AC\core\engines;

use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;
use PDO;

class LanguagesEngine extends BaseEngine
{
  /**
   * Задать название таблицы
   *
   * @param null|string $table_name
   */
  public function setTableName($table_name = 'languages')
  {
    parent::setTableName($table_name);
  }
  
  public function getDefault()
  {
    return Query::sqlQuery('SELECT * FROM ' . Query::tableName($this->tableName()) . ' WHERE `default` = 1', [], true, ['onlyOne' => true]);
  }
}