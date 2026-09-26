<?php

namespace AC\app\config\db_court;

use AC\core\system\db\DB;

class CortDbConfig
{
  /**
   * @var DB
   */
  protected static $baseDB;
  /**
   * @var DB
   */
  protected static $siteDB;
  public static    $tableName;
  public static    $courtData;
  public static    $changeTable;

  static public function generateConfig($fields, $insert = array(), $update = array())
  {
    $config = array();
    if (!empty($insert)) {
      $config['insert'] = array(
        'fields' => $fields,
        'values' => $insert
      );
    }

    if (!empty($update)) {
      $config['update'] = $update;
    }

    return $config;
  }

  public static function deleteNotUse($nameField, $nameTableDonor, $tableName)
  {
    // Удалим лишние перед занесением новых
    $donor  = array_keys(self::$siteDB->getFullDataInTable($nameTableDonor, $nameField));
    $checks = array_keys(self::$siteDB->getFullDataInTable($tableName, $nameField));
    foreach ($checks as $key) {
      if (!in_array($key, $donor)) {
        $delete = 'delete from ' . self::$siteDB->getDbo()->generateTableName($tableName) . ' where ' . $nameField . '="' . $key . '"';
        self::$siteDB->getDbo()->query($delete, array(), false);
      }
    }
  }
}