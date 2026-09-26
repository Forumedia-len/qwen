<?php

namespace AC\core\system\db;




class GenerateQueryDB
{
  /**
   * @var DB
   */
  private $_tables;
  /**
   * @var Dbo
   */
  private $_dbo;

  public function __construct(DB $db)
  {
    $this->_tables = $db->getStructure();
    $this->_dbo    = $db->getDbo();
  }

  /** Сгенерить запросы к базе данных на обновление или создание таблиц
   *  при создании таблиц можно скопировать данные из таблицы
   *
   * @param       $tableName
   * @param       $compareTable
   *
   * @return string
   */
  public function renderCompareOnTableName($tableName, $compareTable)
  {
    $query = '';
    foreach ($compareTable as $action => $compare) {
      switch ($action) {
        case 'update_table':
          $query .= $this->generateUpdateTable($tableName, $compare);
          break;
        case 'create_table':
          $query .= $this->generateCreateTable($tableName, $compare);
          break;
        default:
          break;
      }
    }

    return $query;
  }

  /** Сгенерировать запросы для обновления таблицы
   *
   * @param $tableName
   * @param $_params
   *
   * @return string
   */
  public function generateUpdateTable($tableName, $_params)
  {
    $query     = 'ALTER TABLE ' . $this->_dbo->generateTableName($tableName) . " \n";
    $primary   = array();
    $columns   = array();
    $_queryArr = [];
    if (isset($_params->columns)) {
      foreach ($_params->columns as $column => $actions) {
        foreach ($actions as $action => $params) {
          switch ($action) {
            case 'update_columns':
              $columns['update'][] = $this->generateUpdateColumns(
                $tableName,
                $column,
                array_merge(
                  $actions->params,
                  array('differences' => $params)
                )
              );
              if (isset($params['column_key']['base']) && $params['column_key']['base'] == 'PRI' && !in_array($column, $primary)) {
                $primary[] = $column;
              }
              break;
            case 'create_columns':
              $columns['create'][] = $this->generateCreateColumns(
                $tableName,
                $column,
                array_merge($actions->params, array('differences' => $params))
              );
              if ($params->column_key == 'PRI') {
                $primary = $params->primaryKeys;
              }
              break;
            case 'delete_columns':
              $columns['delete'][] = $this->generateDeleteColumns(
                $tableName,
                $column
              );
              break;
            default:
              break;
          }
        }
      }
    }
    if (!empty($columns)) {
      foreach (array('delete', 'update', 'create') as $act) {
        $this->setQueryColumn(isset($columns[$act]) ? implode(", \n", $columns[$act]) : null, $_queryArr);
      }
    }

    if (count($primary) > 0) {
      $this->setQueryColumn(
        $this->generateUpdateColumnsByColumnKey(
          array('column_key' => array('base' => 'PRI')),
          '',
          $tableName,
          (object)array('primaryKeys' => $primary, 'delPrimary' => $_params->delPrimaryKeys)
        ),
        $_queryArr
      );
    }

    if (isset($_params->params)) {
      $this->setQueryColumn($this->generateUpdateParamsTable($_params->params), $_queryArr);
    }

    // Добавление недостающих индексов отдельным блоком.
    if (isset($_params->indexes_add) && is_array($_params->indexes_add) && !empty($_params->indexes_add)) {
      foreach ($_params->indexes_add as $idxDef) {
        if ($sql = $this->generateAddIndex($tableName, $idxDef)) {
          $this->setQueryColumn($sql, $_queryArr);
        }
      }
    }

    if (!empty($_queryArr)) {
      $query .= implode(", \n", $_queryArr) . ';';
      return $query;
    }

    return '';
  }

  private function setQueryColumn($row, &$query = array())
  {
    if (!empty($row)) {
      $query[] = $row;
    }
  }

  /** Создание запроса для обновления параметров таблицы
   *
   * @param $params
   *
   * @return string
   */
  public function generateUpdateParamsTable($params)
  {
    $query = array();

    foreach ($params as $param => $value) {
      switch ($param) {
        case 'engine':
          $query[] = 'ENGINE=' . $value;
          break;
        case 'charset':
          $query[] = 'CHARSET=' . $value;
          break;
        case 'collate':
          $query[] = 'COLLATE=' . $value;
          break;
        case 'table_collation':
          $query[] = ' CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
          break;
        default :
          break;
      }
    }

    return implode(", \n", $query);
  }

  /**
   * Генерация строки для ALTER TABLE вида "ADD INDEX ...".
   *
   * @param string $tableName
   * @param array  $idxDef
   * @return string
   */
  public function generateAddIndex(string $tableName, array $idxDef): string
  {
    $name = $idxDef['name'] ?? '';
    if ($name === '') {
      return '';
    }

    $unique = !empty($idxDef['unique']);
    $indexType = strtoupper($idxDef['index_type'] ?? 'BTREE');
    $cols = $idxDef['columns'] ?? [];
    if (!is_array($cols) || empty($cols)) {
      return '';
    }

    $colsSql = [];
    foreach ($cols as $c) {
      $colName = $c['name'] ?? '';
      if ($colName === '') {
        continue;
      }
      $subPart = $c['sub_part'] ?? null;
      $colsSql[] = '`' . $colName . '`' . ($subPart ? '(' . (int)$subPart . ')' : '');
    }

    if (empty($colsSql)) {
      return '';
    }

    $uniqueSql = $unique ? 'UNIQUE ' : '';
    $typeSql = '';
    if ($indexType === 'FULLTEXT') {
      $typeSql = 'FULLTEXT ';
    } elseif ($indexType === 'SPATIAL') {
      $typeSql = 'SPATIAL ';
    }

    // ADD UNIQUE FULLTEXT INDEX `name` (`col1`, `col2`)
    return 'ADD ' . $uniqueSql . $typeSql . 'INDEX `' . $name . '` (' . implode(', ', $colsSql) . ')';
  }

  /** Создание запросов к базе данных для создание таблицы
   *
   * @param $tableName
   *
   * @param $params
   *
   * @return string|string[]
   */
  public function generateCreateTable($tableName, $params)
  {
    return str_replace('CREATE TABLE ', 'CREATE TABLE ', $params['query']);
  }

  /**
   * Форматирует тип колонки, не изменяя регистр значений ENUM и SET.
   */
  private function formatColumnType(string $columnType): string
  {
    if (preg_match('/^(enum|set)(?=\s*\()/i', $columnType, $matches) === 1) {
      return strtoupper($matches[1]) . substr($columnType, strlen($matches[1]));
    }

    return mb_strtoupper($columnType);
  }

  public function generateUpdateColumns($tableName, $columnName, $params)
  {
    $query = [];
    $base  = $params['base'];
    $site  = $params['site'];
    $deff  = $params['differences'];
    $first = isset($deff['ordinal_position'])
    && $deff['ordinal_position']['base'] === 1
    && $deff['ordinal_position']['base'] != $deff['ordinal_position']['site']
      ? ' FIRST' : '';

    if (isset($deff['column_key']) && $deff['column_key']['base'] != 'PRI') {
      $query[] = $this->generateUpdateColumnsByColumnKey($deff, $columnName, $tableName, $base);
    }
    return 'CHANGE `' . $site->column_name . '` `' . $base->column_name . '` '
      . $this->formatColumnType($base->column_type)
      . ($base->column_default !== 'NULL' ? ($base->is_nullable == 'YES' ? ' NULL' : ' NOT NULL') : '')
      . (($base->column_default !== null) ? ' DEFAULT ' . $base->column_default  : '')
      . (!empty($base->column_comment) ? ' COMMENT \'' . $base->column_comment . '\'' : '')
      . (!empty($base->extra) ? ' ' . mb_strtoupper($site->extra) : '')
      . $first
      . implode(", \n", $query);
  }

  public function generateUpdateColumnsByColumnKey($deff, $columnName, $tableName, $params)
  {
    $query   = [];
    $indexes = $this->_tables[$tableName]->indexes;
    switch ($deff['column_key']['base']) {
      case '':
        if ($deff['column_key']['site'] == 'MUL') {
          if ($this->checkIndexForForeignKey($columnName, $tableName)) {
            $query[] = 'DROP FOREIGN KEY `' . $indexes[$columnName]['Key_name'] . '`,' . "\n";
          }
          $query[] = 'DROP INDEX `' . $indexes[$columnName]['Key_name'] . '`';
        }
        break;
      case 'PRI':
        if($params->delPrimary) {
          $query[] = 'DROP PRIMARY KEY';

        }
        $query[] = 'ADD PRIMARY KEY (' . '`' . implode('` , `', $params->primaryKeys) . '`' . ')';
        break;
    }

    return implode(", \n", $query);
  }

  public function generateCreateColumns($tableName, $column, $params)
  {
    $base = $params['base'];

    return 'ADD COLUMN `' . $column . '` '
      . $this->formatColumnType($base->column_type)
      . ($base->column_default !== 'NULL' ? ($base->is_nullable == 'YES' ? ' NULL' : ' NOT NULL') : '')
      . (($base->column_default !== null) ? ' DEFAULT ' . $base->column_default  : '')
      . (!empty($base->column_comment) ? ' COMMENT \'' . $base->column_comment . '\'' : '')
      . ($base->after == $base->column_name && $base->ordinal_position == 1 ? ' FIRST' : '')
      . ($base->after != $base->column_name && $base->ordinal_position != 1 ? ' AFTER `' . ($base->after) . '`' : '');
  }

  public function generateDeleteColumns($tableName, $column)
  {
    return ' 
     DROP COLUMN `' . $column . '`';
  }

  public function checkIndexForForeignKey($index_key, $tableName)
  {
    return in_array($index_key, array_keys($this->_tables[$tableName]->foreignKeys));
  }

  /** Генерация sql запроса для вставки строк в таблицу
   *
   * @param $tableName - имя таблицы
   * @param $data      - массив с полями и значениями
   *                   ['fields' => [],
   *                   'values' => []
   *                   ]
   *
   * @return string
   */
  public function generateInsertData($tableName, $data)
  {
    if (isset($data['fields']) && count($data['fields']) > 0
      && isset($data['values']) && count($data['values']) > 0) {
      $values = array();
      foreach ($data['values'] as $value) {
        foreach ($value as $key => $item) {
          if($item === null) {
            $value->$key = 'NUll';
          }
        }
        $values[] = str_replace('\'NUll\'', 'null','(\'' . implode('\', \'', (array)$value) . '\')');      }

      return 'INSERT INTO ' . $this->_dbo->generateTableName($tableName)
        . ' (' . '`' . implode('`, `', $data['fields']) . '`' . ')'
        . ' VALUES ' . "\n"
        . implode(", \n", $values)
        . ';';
    }

    return '';
  }


  public function generateUpdateData($tableName, $data, $as = '')
  {
    if (isset($data['where']) && count($data['where']) > 0
      && isset($data['set']) && count($data['set']) > 0) {
      $where = $this->renderDataForUpdate($data['where'], ' AND ', $as);
      $set   = $this->renderDataForUpdate($data['set'], ', ', $as);

      return 'UPDATE ' . $this->_dbo->generateTableName($tableName) . ($as !== '' ? ' as ' . $as : '') . ' SET '
        . $set
        . ($where ? ' WHERE ' . $where : '')
        . ';';
    }

    return '';
  }

  protected function renderDataForUpdate($data, $separator = ", \n", $as = '')
  {
    $result = [];
    foreach ($data as $alias => $value) {
      if ($value == 'is null' || $value == 'is not null') {
        $result[] = ($as !== '' ? $as . '.' : '') . $alias . ' ' . $value;
      } else {
        $result[] = ($as !== '' ? $as . '.' : '') . $alias . ' = \'' . $value . '\'';
      }
    }

    return implode($separator, $result);
  }

  public function generateTruncateTable($tableName)
  {
    return 'TRUNCATE TABLE ' . $this->_dbo->generateTableName($tableName);
  }
}
