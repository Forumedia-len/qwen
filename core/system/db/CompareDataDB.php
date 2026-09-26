<?php
namespace AC\core\system\db;

class CompareDataDB
{

  public static function compareTableSetBase($tableName, DB $currentDB, DB $baseDB)
  {
    $result = [];
    if(isset($currentDB->getStructure()[$tableName]) && isset($baseDB->getStructure()[$tableName])) {
      $primaryKeys = array_keys($baseDB->getPrimaryKeys($tableName));
      foreach ($primaryKeys as $key => $primaryKey) {
        if(!in_array($primaryKey , $currentDB->getFields($tableName))) {
          unset($primaryKeys[$key]);
        }
      }

      $dataBaseDB = self::renderDataByPrimaryKey($baseDB->getFullDataInTable($tableName), $primaryKeys);
      $dataCurrentDB = self::renderDataByPrimaryKey($currentDB->getFullDataInTable($tableName), $primaryKeys);

      foreach ($dataBaseDB as $keyBase => $rowBase) {
        if(!isset($dataCurrentDB[$keyBase])) {
          $result['insert']['values'][$keyBase] = $rowBase;
        }
      }
      if(isset($result['insert'])) {
        $result['insert']['fields'] = $baseDB->getFields($tableName);
      }
    }

    return $result;
  }

  /**
   * Перебирает и формирует массив с ключами заданными в $primaryKeys
   *  если $primaryKeys - массив то формируем ключ из значений разделенных |
  */
  public static function renderDataByPrimaryKey($data, $primaryKeys)
  {
    $result = [];
    if (empty($primaryKeys)) {
      return $data;
    }

    foreach ($data as $row) {
      if(is_object($row) || is_array($row)) {
        $key = [];
        foreach ($primaryKeys as $primaryKey) {
          $key[] = is_object($row) ? $row->{$primaryKey} : $row[$primaryKey];
        }
        $result[implode('|', $key)] = $row;
      } else {
        $result[] = $row;
      }

    }

    return $result;
  }

}