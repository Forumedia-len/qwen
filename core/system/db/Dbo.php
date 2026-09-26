<?php

namespace AC\core\system\db;

use AC\app\config\DBConfig;

use AC\core\system\helpers\JsonHelper;
use PDO;
use PDOException;
use PDOStatement;

class Dbo
{
  private static ?Dbo $instance = null;

  /** @var array<string, bool> Кэш checkTable() на время запроса (снимает повторные SHOW TABLES) */
  private static array $checkTableResultCache = [];

  /** @var array<string, bool> Кэш checkField() на время запроса (снимает повторные SHOW COLUMNS) */
  private static array $checkFieldResultCache = [];

  private string $dbname;
  private string $user;
  private string $password;
  private string $prefixTable;
  private string $charset;
  private string $type;
  private string $host;
  private array $options = [];

  /**
   * @var PDO
   */
  private PDO $dbo;

  /**
   * Конструктор класса Dbo.
   *
   * @param ?array $options Параметры подключения к БД
   *
   * @throws PDOException
   */

  private function __construct(?array $options = null)
  {
    $this->renderOptions($options);
    $this->dbo = new PDO(
      $this->type . ':host=' . $this->host . ';dbname=' . $this->dbname,
      $this->user,
      $this->password,
      $this->options
    );
    $this->dbo->exec("SET NAMES " . $this->charset);
  }

  public function setBD($new_bd, $new_prefix)
  {
    try {
      $result = $this->query("USE " . $new_bd, [], false);
      if ($result) {
        $this->dbname      = $new_bd;
        $this->prefixTable = $new_prefix;
      }
      return $result;
    } catch (PDOException) {
      return false;
    }

  }

  /**
   * Обработка опций подключения к БД.
   *
   * @param ?array $options Опции подключения
   */
  private function renderOptions(array $options = null): void
  {
    $options       = $options ?? DBConfig::getConfig('site');
    $this->options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    foreach ($options as $key => $value) {
      if ($key == 'options') {
        $value = array_merge($this->options, $value);
      }
      $this->{$key} = $value;
    }
  }

  /**
   * Выполнение SQL-запроса.
   *
   * @param string     $sql    SQL-запрос
   * @param array      $params Параметры запроса
   * @param bool       $query  Флаг выборки данных
   * @param array|null $config Дополнительные настройки
   *
   * @return mixed Результат выполнения запроса
   * @throws PDOException
   */
  public function query(string $sql, array $params = [], bool $query = true, ?array $config = null): mixed
  {
    try {
      if (empty($config) || !isset($config['style'])) {
        $config['style']    = PDO::FETCH_ASSOC;
        $config['argument'] = $config['argument'] ?? null;
      }
      $stmt = $this->prepare($sql);

      return $this->execute($stmt, $params, $query, $config);
    } catch (\PDOException $e) {
      $i = 3;
      foreach ($e->getTrace() as $key => $item) {
        if ($item['function'] === 'sqlQuery' && $item['class'] === 'AC\core\system\db\Query') {
          $i = $key;
        }
      }
      Debug()::log(
        $e->getMessage() . ' -- ' .
        'File: ' . $e->getTrace()[$i]['file'] . ':' . $e->getTrace()[$i]['line'] . ' -- ' .
        'SqlQuery: ' . str_replace("\t", ' ',
          str_replace(["\r", "\n"], '', ($sql))) . ' -- ' .
        'Params: ' . JsonHelper::encode([$params, $query, $config]) .
        (LOCAL_SERVER ? "\n" . $e->getTraceAsString() : '')
        , 'db');
      return false;
    }
  }

  public function fetchAll(PDOStatement $stmt, ?array $config = [])
  {
    $style           = $config['style'] ?: PDO::FETCH_ASSOC;
    $argument        = $config['argument'] ?? null;
    $constructorArgs = $config['constructorArgs'] ?? [];

    // Определяем, какие аргументы передать в fetchAll()
    switch ($style) {
      case PDO::FETCH_CLASS:
        $className = $argument ?: 'stdClass';
        $result    = $stmt->fetchAll($style, $className, $constructorArgs);
        break;

      case PDO::FETCH_COLUMN:
        $columnIndex = $argument ?: 0;
        $result      = $stmt->fetchAll($style, $columnIndex);
        break;

      case PDO::FETCH_FUNC:
        if ($argument === null) {
          throw new \InvalidArgumentException(
            "PDO::FETCH_FUNC requires a function name in 'argument'"
          );
        }
        $result = $stmt->fetchAll($style, $argument);
        break;

      default:
        // Все остальные режимы, включая комбинированные:
        // PDO::FETCH_ASSOC, PDO::FETCH_NUM,
        // PDO::FETCH_GROUP | PDO::FETCH_ASSOC и т.д.
        $result = $stmt->fetchAll($style);
    }

    return $this->renderResult($result, $config);
  }

  public function renderResult($result, ?array $config = [])
  {
    return match ($this->renderConfigByResultOption($config)) {
      'onlyOne' => $result[0] ?? null,
      default   => $result,
    };
  }

  private function renderConfigByResultOption($config): string
  {
    if ((isset($config['onlyOne']) && $config['onlyOne']) || (isset($config['OnlyOne']) && $config['OnlyOne'])) {
      return 'onlyOne';
    }

    return 'default';
  }

  /**
   * Получение последнего ID, вставленного в БД.
   *
   * @return ?int
   */
  public function lastInsertId(): ?int
  {
    return (int)$this->dbo->lastInsertId();
  }

  /**
   * Получение экземпляра класса Dbo.
   *
   * @param ?array $config Параметры подключения
   * @param bool   $new    Флаг создания нового экземпляра
   * @param bool   $single Флаг создания одиночного экземпляра
   *
   * @return self
   */
  public static function getInstance(array $config = null, bool $new = false, bool $single = false): self
  {
    if ($single) {
      return new self($config);
    }

    if (self::$instance === null || $new) {
      self::$instance = new self($config);
    }

    return self::$instance;
  }

  /**
   * Получение префикса таблиц.
   *
   * @return string
   */
  public function getPrefixTable(): string
  {
    return $this->prefixTable;
  }

  /**
   * Получение имени таблицы без префикса.
   *
   * @param string $tableName Имя таблицы
   *
   * @return string
   */
  public function getNameTableWithoutPrefix(string $tableName): string
  {
    return str_replace($this->getPrefixTable(), '', $tableName);
  }

  /**
   * Получение структуры таблиц из базы данных.
   *
   * @param array       $fields Поля для выборки
   * @param string|null $dbName Имя базы данных
   *
   * @return array
   */
  public function getStructureTables(array $fields = [], ?string $dbName = null): array
  {
    $dbName = $dbName ?: $this->dbname;

    if (empty($fields)) {
      $fields = '*';
    } else {
      $fields = implode(',', $fields);
    }
    $tables  = [];
    $_tables = $this->query(
      'SELECT ' . $fields . ' FROM information_schema.`tables` WHERE table_schema = "' . $dbName . '"',
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'argument' => 'stdClass']
    );
    foreach ($_tables as $table) {
      if (strpos($table->TABLE_NAME, $this->prefixTable) === 0 || $dbName != $this->dbname) {
        $tables[$this->getNameTableWithoutPrefix($table->TABLE_NAME)] = $table;
      }
    }

    return $tables;
  }

  /**
   * Получение индексов таблицы.
   *
   * @param string $tableName Имя таблицы
   *
   * @return array
   */
  public function getIndexes(string $tableName): array
  {
    $indexes = [];
    $byName  = [];
    foreach ($this->query('SHOW INDEX FROM ' . $this->getPrefixTable() . $tableName) as $row) {
      // Backward compatibility: старый формат "по имени колонки".
      $indexes[$row['Column_name']] = $row;

      // Новый формат: полная группировка по имени индекса.
      $keyName = $row['Key_name'] ?? '';
      if ($keyName === '') {
        continue;
      }
      if (!isset($byName[$keyName])) {
        $byName[$keyName] = [
          'name'       => $keyName,
          'unique'     => ((int)($row['Non_unique'] ?? 1) === 0),
          'index_type' => strtoupper($row['Index_type'] ?? 'BTREE'),
          'columns'    => [],
        ];
      }

      $seq = (int)($row['Seq_in_index'] ?? 0);
      if ($seq <= 0) {
        $seq = count($byName[$keyName]['columns']) + 1;
      }

      $subPart                           = $row['Sub_part'] ?? null;
      $byName[$keyName]['columns'][$seq] = [
        'name'     => $row['Column_name'],
        'sub_part' => ($subPart !== null && $subPart !== '' && (int)$subPart > 0) ? (int)$subPart : null,
      ];
    }

    foreach ($byName as &$idxDef) {
      ksort($idxDef['columns']);
      $idxDef['columns'] = array_values($idxDef['columns']);
    }

    // Служебная секция для новых сценариев сравнения/генерации индексов.
    $indexes['__by_name'] = $byName;

    return $indexes;
  }

  /**
   * Получение первичных ключей таблицы.
   *
   * @param string $tableName Имя таблицы
   *
   * @return array
   */
  public function getPrimaryKeys(string $tableName): array
  {
    $keys = [];
    foreach ($this->query('SHOW KEYS FROM ' . $this->getPrefixTable() . $tableName . ' WHERE Key_name = \'PRIMARY\'') as $row) {
      $keys[$row['Column_name']] = $row;
    }

    return $keys;
  }

  /**
   * Получение внешних ключей таблицы.
   *
   * @param string $tableName Имя таблицы
   *
   * @return array
   */
  public function getForeignKeys(string $tableName): array
  {
    $keys = [];
    foreach (
      $this->query(
        ' SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = \'' . $this->dbname . '\'
        AND TABLE_NAME = \'' . $this->getPrefixTable() . $tableName . '\'
        AND CONSTRAINT_NAME <> \'PRIMARY\'
        AND REFERENCED_TABLE_NAME is not null'
      ) as $row
    ) {
      $keys[$row['COLUMN_NAME']] = $row;
    }

    return $keys;
  }

  /**
   * Получение структуры таблицы.
   *
   * @param string $tableName Имя таблицы
   * @param array  $fields    Поля для выборки
   *
   * @return array
   */
  public function getStructureTable(string $tableName, array $fields = []): array
  {
    if (empty($fields)) {
      $fields = '*';
    } else {
      $fields = implode(',', $fields);
    }

    return $this->query(
      'SELECT ' . $fields . ' FROM information_schema.`COLUMNS` WHERE TABLE_SCHEMA =\'' . $this->dbname . '\' and TABLE_NAME=\'' . $this->getPrefixTable() . $tableName . '\'',
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'argument' => 'stdClass']
    );
  }

  /**
   * Получение полей таблицы.
   *
   * @param string $tableName Имя таблицы
   *
   * @return array
   */
  public function getFieldsTable(string $tableName): array
  {
    static $tableFields;

    if (!isset($tableFields[$tableName])) {
      foreach ($this->getStructureTable($tableName, ['COLUMN_NAME']) as $item) {
        $tableFields[$tableName][] = $item->COLUMN_NAME;
      }
    }

    return $tableFields[$tableName];
  }


  /**
   * Получение массива с именами таблиц.
   *
   * @param bool        $use_prefix Использовать префикс
   * @param string|null $bdName     Имя базы данных
   *
   * @return array
   */
  public function getNameTables(bool $use_prefix = false, ?string $bdName = null): array
  {
    $bdName = $bdName ?: $this->dbname;
    $result = [];
    foreach ($this->query('show tables from ' . $bdName) as $t) {
      if ($use_prefix) {
        $result[] = $t['Tables_in_' . $bdName];
      } else {
        $result[] = $this->getNameTableWithoutPrefix($t['Tables_in_' . $bdName]);
      }
    }

    return $result;
  }

  /**
   * Генерация имени таблицы с префиксом и именем базы данных.
   *
   * @param string      $tableName Имя таблицы
   * @param ?string     $baseName  Использовать имя базы данных
   * @param string|null $prefix    Префикс
   *
   * @return string `{DB_DATABASE_NAME}`.`{DB_TABLE_PREFIX}.{$tableName}`
   */
  public function generateTableName(string $tableName, ?string $baseName = null, ?string $prefix = null): string
  {
    return '`' . ($baseName ?? $this->dbname) . '`.' . '`' . ($prefix ?? $this->getPrefixTable()) . $tableName . '`';
  }

  /**
   * Получение всех данных из таблицы.
   *
   * @param string $tableName Имя таблицы
   *
   * @return array|bool
   */
  public function getAllDataFromTable(string $tableName)
  {
    return $this->query(
      'select * from ' . $this->getPrefixTable() . $tableName,
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'argument' => 'stdClass']
    );
  }

  /**
   * Генерация строки CREATE TABLE.
   *
   * @param string      $tableName    Имя таблицы
   * @param string|null $changePrefix Новый префикс
   * @param ?string     $baseName     Использовать имя базы данных
   *
   * @return string
   */
  public function generateCreateTableAsString(string $tableName, ?string $changePrefix = null, ?string $baseName = null): string
  {
    $res   = $this->query('SHOW CREATE TABLE ' . $this->getPrefixTable() . $tableName);
    $query = str_replace('`' . $res[0]['Table'] . '`', $this->generateTableName($tableName, $baseName), $res[0]['Create Table']);

    return $changePrefix !== null ? str_replace(
        $this->getPrefixTable(),
        $changePrefix,
        $query
      ) . ';' : $query;
  }

  /**
   * Проверка существования таблицы.
   *
   * @param string      $tableName Имя таблицы
   * @param bool        $addPrefix Добавить префикс
   * @param string|null $bdName    Имя базы данных
   *
   * @return bool
   */
  public function checkTable(string $tableName, bool $addPrefix = true, ?string $bdName = null): bool
  {
    $bdName      = $bdName ?: $this->dbname;
    $tableName   = ($addPrefix ? $this->getPrefixTable() : '') . $tableName;
    $likePattern = (!$addPrefix ? '%' : '') . $tableName;
    $cacheKey    = $bdName . "\0" . $likePattern;
    if (array_key_exists($cacheKey, self::$checkTableResultCache)) {
      return self::$checkTableResultCache[$cacheKey];
    }
    $result                                 = (bool)count($this->query('SHOW TABLES FROM `' . $bdName . '` LIKE \'' . $likePattern . '\''));
    self::$checkTableResultCache[$cacheKey] = $result;

    return $result;
  }

  /**
   * Получение полного имени таблицы по части.
   *
   * @param string      $tableName Часть имени таблицы
   * @param string|null $bdName    Имя базы данных
   * @param bool        $useEnd    Использовать конец строки
   *
   * @return array|bool
   */
  public function getFullTableName(string $tableName, ?string $bdName = null, bool $useEnd = true)
  {
    $tables = [];
    $bdName = $bdName ?: $this->dbname;
    foreach ($this->query('SHOW TABLES FROM `' . $bdName . '` LIKE \'%' . $tableName . ($useEnd ? '%' : '') . '\'', [], true,
      ['style' => PDO::FETCH_NUM]) as $table) {
      $tables[] = $table[0];
    }

    return $tables;
  }

  /**
   * Получение имени базы данных.
   *
   * @return string
   */
  public function getNameDB(): string
  {
    return $this->dbname;
  }

  /**
   * Получение списка баз данных.
   *
   * @return array
   */
  public function showDataBase(): array
  {
    $dataBase = [];

    foreach ($this->query('SHOW DATABASES') as $dbName) {
      $dataBase[] = $dbName['Database'];
    }

    return $dataBase;
  }

  /**
   * Проверка существования поля в таблице.
   *
   * @param string $fieldName Имя поля
   * @param string $tableName Имя таблицы
   *
   * @return bool
   */
  public function checkField(string $fieldName, string $tableName): bool
  {
    $fullTableName = $this->generateTableName($tableName);
    $cacheKey = $fullTableName . "\0" . $fieldName;
    if (array_key_exists($cacheKey, self::$checkFieldResultCache)) {
      return self::$checkFieldResultCache[$cacheKey];
    }

    $result = ($res = $this->query('SHOW COLUMNS FROM ' . $fullTableName . ' LIKE \'' . $fieldName . '\''))
      && is_array($res)
      && count($res) > 0;
    self::$checkFieldResultCache[$cacheKey] = $result;

    return $result;
  }

  /**
   * Добавление поля в таблицу.
   *
   * @param string $fieldName Имя поля
   * @param string $tableName Имя таблицы
   */
  public function addFiled(string $fieldName, string $tableName)
  {

  }

  /**
   * Начать транзакцию.
   *
   * @return bool
   * @throws PDOException
   */
  public function beginTransaction(): bool
  {
    return $this->dbo->beginTransaction();
  }

  /**
   * Проверить наличие активной транзакции.
   *
   * @return bool
   */
  public function inTransaction(): bool
  {
    return $this->dbo->inTransaction();
  }

  /**
   * Подтвердить транзакцию.
   *
   * @return bool
   * @throws PDOException
   */
  public function commit(): bool
  {
    return $this->dbo->commit();
  }

  /**
   * Откатить транзакцию.
   *
   * @return bool
   * @throws PDOException
   */
  public function rollBack(): bool
  {
    return $this->dbo->rollBack();
  }

  public function prepare(string $sql): PDOStatement
  {
    return $this->dbo->prepare($sql);
  }

  /**
   * Выполнить подготовленный запрос.
   *
   * @param PDOStatement $stmt
   * @param array        $params Параметры запроса
   * @param bool         $query
   * @param array|null   $config
   *
   * @return mixed Количество затронутых строк или результат выполнения
   */
  public function execute(PDOStatement $stmt, array $params = [], bool $query = true, ?array $config = null): mixed
  {
    $result = $stmt->execute($params);

    return $query ? $this->fetchAll($stmt, $config) : $result;
  }
}
