<?php

namespace AC\core\system\db;

class DataDB
{

  public static function getAllData($dbo, $tableName)
  {
    return $dbo->getAllDataFromTable($tableName);
  }
}