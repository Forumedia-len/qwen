<?php

namespace AC\core\system\db;

use AC\app\config\DBConfig;
use Service;

class Query
{
  private string      $tableName;
  private Dbo         $db;
  static private ?int $lastId;

  static public string $prefix;

  private static float $allWorkTime = .0;

  public function __construct($tableName = null)
  {
    $this->db     = Dbo::getInstance();
    self::$prefix = $this->db->getPrefixTable();
    if ($tableName) {
      $this->setTableName($tableName);
    }
  }

  public static function setBD($new_bd, $new_prefix): void
  {
    if (self::getDB()->setBD($new_bd, $new_prefix)) {
      self::$prefix = self::getDB()->getPrefixTable();
    }
  }

  /**
   * @param string $sql
   * @param array  $params
   * @param bool   $query
   * @param ?array $config
   *
   * @return mixed
   */
  public static function sqlQuery(string $sql, array $params = [], bool $query = true, ?array $config = null)
  {
    $_query = new Query();

    return self::executeSql($_query->db, $sql, $params, $query, $config);
  }

  /**
   * Выполнить один запрос через отдельное подключение с расширенными правами.
   *
   * @param string     $sql
   * @param array      $params
   * @param bool       $query
   * @param array|null $config
   *
   * @return mixed
   */
  public static function sqlQueryWithExtendedPrivileges(
    string $sql,
    array $params = [],
    bool $query = true,
    ?array $config = null
  ): mixed {
    $db = Dbo::getInstance(DBConfig::getExtendedPrivilegesConfig(), false, true);

    return self::executeSql($db, $sql, $params, $query, $config);
  }

  /**
   * Выполнить запрос через указанное подключение.
   */
  private static function executeSql(
    Dbo $db,
    string $sql,
    array $params = [],
    bool $query = true,
    ?array $config = null
  ): mixed {
    testTime('sqlQuery', false);
    $result = $db->query($sql, $params, $query, $config);
    testTime('sqlQuery', (Service::request()->check('debugSql') || (defined('DEBUG_SQL') && DEBUG_SQL)),
      ['sql:' => $sql, 'params:' => $params]);
    self::$lastId = $db->lastInsertId();
    return $result;
  }

  public function setTableName($tableName = null)
  {
    if ($tableName) {
      $this->tableName = self::$prefix . $tableName;
    }
  }

  /**
   * Возвращает имя таблицы с префиксом и названием базы данных
   *
   * @return string `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}.{$tableName}`
   */
  public static function tableName(string $tableName): string
  {
    return self::getDB()->generateTableName($tableName);
  }

  public function getTableName(?string $tableName = null): string
  {
    if ($tableName !== null) {
      return self::$prefix . $tableName;
    }

    return $this->tableName;
  }

  public static function getLastId(): ?int
  {
    return self::$lastId;
  }

  /**
   *  Генерация условий для запроса
   *
   * @param array $conditions
   * @param array $params
   *
   * @return string
   */
  public function generateQueryCondition(array $conditions = [], array $params = []): string
  {
    $condition = [];
    if ($conditions) {
      foreach ($conditions as $key => $value) {
        if (is_numeric($key)) {
          $condition[] = $value;
        } else {
          $condition[] = $key . ' = ' . (is_string($value) ? "'" . $value . "'" : $value);
        }
      }
    }

    return !empty($condition) ? ' WHERE ' . implode(' AND ', $condition) : '';
  }

  public static function getDB()
  {
    return (new Query())->db;
  }

  public static function setWhereWithPlaceholder($fieldName, $value, &$where, &$placeholder, $checkFalse = true)
  {
    if (!$checkFalse || $value !== false) {
      $where                      .= ($where == '' ? ' WHERE ' : ' AND ') . "$fieldName=:$fieldName";
      $placeholder[":$fieldName"] = "$value";
    }

  }

  public static function getAllWorkTimeScripts()
  {
    return self::$allWorkTime;
  }

  /**
   * Начать транзакцию.
   */
  public static function beginTransaction(): void
  {
    self::getDb()->beginTransaction();
  }

  /**
   * Проверить наличие активной транзакции.
   *
   * @return bool
   */
  public static function inTransaction(): bool
  {
    return self::getDb()->inTransaction();
  }

  /**
   * Подтвердить транзакцию.
   */
  public static function commit(): void
  {
    self::getDb()->commit();
  }

  /**
   * Откатить транзакцию.
   */
  public static function rollBack(): void
  {
    self::getDb()->rollBack();
  }
}


function testTime($name, $flgShow = false, $param = [])
{
  $mkt            = microtime(true);
  $dmkt           = $mkt - ($GLOBALS['mkt'] ?? 0);
  $GLOBALS['mkt'] = $mkt;
  $res            = $name . ' - ' . $dmkt . "";
  $return[$res]   = [];
  if (!empty($param)) {
    if ($flgShow) {
      $param['trace'] = debug_backtrace();
    }
    $return[$res] = $param;
  }
  if ($flgShow && ($dmkt > 0.01 || Service::request()->_('debugSql') == 'all') || (defined('DEBUG_SQL') && DEBUG_SQL === 'all')) {
    Service::view()->addJsCode('console.log(' . json_encode($return, JSON_UNESCAPED_UNICODE) . ')');
  }

  return $dmkt;
}
