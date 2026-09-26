<?php

namespace AC\core\system\db;

use AC\app\config\DBConfig;

use AC\core\system\helpers\ArrayHelper;

class DB
{
  /**
   * @var Dbo
   */
  private Dbo $_dbo;

  private        $_structure;
  private        $tableData;
  /**
   * @var array {DB}
   */
  private static array $_instance;


  private function __construct($alias, $config = array())
  {
    $this->_dbo = Dbo::getInstance(DBConfig::getConfig($alias, $config), true, true);
    $this->instanceStructure();
  }

  public static function instance($alias = 'site', $new = false, $config = array())
  {
    if (!isset(self::$_instance[$alias]) || $new) {
      self::$_instance[$alias] = new self($alias, $config);
    }

    return self::$_instance[$alias];
  }


  public function convertCharset()
  {
    $convert = false;
    foreach ($this->getStructure() as $tableName => $tableStructure) {
      if ($tableStructure->table_collation !== 'utf8mb4_unicode_ci') {
        $convert = true;
        $this->fulfillRequestToDataBase(
          'ALTER TABLE ' . $this->_dbo->generateTableName(
            $tableName
          ) . ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
      }
    }

    return $convert;
  }


  /** Выполнить запрос к базе данных
   *
   * @param $request - запрос в формате sql
   */
  public function fulfillRequestToDataBase($request, $placeholder = array(), $getResult = false, $config = array())
  {
    return $this->_dbo->query($request, $placeholder, $getResult, $config);
  }

  /** Выполнить запросы из массива
   *
   * @param $queryArray
   */
  public function executingAllQuery($queryArray, $debug = true)
  {
    foreach ($queryArray as $query) {
      if (!empty($query)) {
        $check = false;
        $res = array('Query' => '<span style="color: #0c0c57">' . htmlspecialchars($query) . '</span>', 'Result' => '<span style="color: red;font-size: 16px;font-weight: bold">FALSE</span>');
        if ($this->fulfillRequestToDataBase($query)) {
          $check = true;
          $res['Result'] = '<span style="color: green;font-size: 16px;font-weight: bold">OK</span>';
        }
        if($debug) {
          Debug()::dBresult($res, $check ? '#d1e7dd' : '#f8d7da');
        }
      }
    }
  }

  public function getNameTables()
  {
    return array_keys($this->_structure);
  }

  public function getStructureTable($tableName)
  {
    return $this->_structure[$tableName] ?? new \stdClass();
  }

  public function getStructureColumn($column, $tableName)
  {
    return $this->getStructureTable($tableName)->columns[$column];
  }

  public function getColumnsByTableName($tableName)
  {
    return array_keys($this->getStructureTable($tableName)->columns);
  }

  public function getFullDataInTable($tableName, $key = null)
  {
    if (!isset($this->tableData[$tableName])) {
      $result = DataDB::getAllData($this->_dbo, $tableName);
      if ($key !== null) {
        $_re = array();
        foreach ($result as $item) {
          $_re[$item->{$key}] = $item;
        }
        $result = $_re;
      }
      $this->tableData[$tableName] = $result;
    }

    return $this->tableData[$tableName];
  }

  public function getDataTableByFieldsAndValues($tableName)
  {
    $result['fields'] = $this->getColumnsByTableName($tableName);
    $result['values'] = $this->getFullDataInTable($tableName);

    return $result;
  }

  public function getPrimaryKey($tableName)
  {
    return isset($this->getStructureTable($tableName)->primaryKey) ? $this->getStructureTable($tableName)->primaryKey : null;
  }

  public function getPrimaryKeys($tableName)
  {
    return $this->getStructureTable($tableName)->primaryKeys;
  }

  public function getFields($tableName, $primaryKey = true)
  {
    $structure = $this->getStructureTable($tableName);
    $key       = $this->getPrimaryKey($tableName);
    $fields    = array_keys($structure->columns);

    if (!$primaryKey && $key !== null) {
      ArrayHelper::unsetByValue($fields, $key);
    }

    return $fields;
  }

  public function getStructure()
  {
    return $this->_structure;
  }

  public function getDbo(): Dbo
  {
    return $this->_dbo;
  }

  public function getLastAutoIncrement($tableName)
  {
    return $this->getStructure()[$tableName]->auto_increment;
  }

  public function generateTableName($tableName, $prefix = null)
  {
    return $this->_dbo->generateTableName($tableName, null, $prefix);
  }

  public function createDB($dbname, $charsetName = 'utf8mb4', $collationName = "utf8mb4_unicode_ci"): bool
  {
    return $this->getDbo()->query(
        'CREATE DATABASE IF NOT EXISTS ' . $dbname . ' DEFAULT CHARACTER SET ' . $charsetName . ' DEFAULT COLLATE ' . $collationName . ';'
      ) !== false;
  }

  public function setGrantByDBName($dbname, $tables = '*', $privileges = 'ALL PRIVILEGES')
  {
    return $this->getDbo()->query('GRANT ' . $privileges . ' ON ' . $dbname . ($tables ? '.' . $tables : '') . ' TO \'root\'@\'localhost\';');
  }

  public function checkDB($dbname): bool
  {
    return !empty($this->getDbo()->query('SHOW DATABASES LIKE "' . $dbname . '";'));
  }

  /**
   * Перезаписать или установить структуру базы данных
   */
  public function instanceStructure($overwrite = true): void
  {
    if (empty($this->_structure) || $overwrite) {
      $this->_structure = StructureDB::parseDbStructure($this->_dbo);
      if ($this->convertCharset()) {
        $this->_structure = StructureDB::parseDbStructure($this->_dbo);
      }
    }
  }
}