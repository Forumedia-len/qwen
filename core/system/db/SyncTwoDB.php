<?php

namespace AC\core\system\db;



/**
 * Class CopySiteOnNewBase
 * @property DB $db_site
 * @property DB $db_base
 */
class SyncTwoDB
{
  const SITE_ALIAS_NAME = 'site';
  const BASE_ALIAS_NAME = 'base';
  private $_dbAlias = array('site', 'base');
  private $_data    = array();
  private $_compare;
  private $_config;
  /**
   * @var Dbo
   */
  private $_dbo;

  public function __construct($config = array())
  {
    $this->_config = $config;
    foreach ($this->_dbAlias as $alias) {
      $this->instanceDb($alias);
    }

    $this->_dbo = $this->{'db_' . self::SITE_ALIAS_NAME}->getDbo();
  }

  public function __set($name, $value)
  {
    $this->_data[$name] = $value;
  }

  public function __get($name)
  {
    return isset($this->_data[$name]) ? $this->_data[$name] : null;
  }

  private function getDboByAlias($alias)
  {
    return $this->{'db_' . $alias}->getDbo();
  }

  private function getStructureDb($alias)
  {
    return $this->{'db_' . $alias}->getTables();
  }

  public function instanceDb($alias)
  {
    $this->{'db_' . $alias} = DB::instance($alias);
  }

  /** Получить массив сравнения
   *  ключи - имена таблиц
   *  значения - действия с изменениями которые нужно привести, чтобы сделать их идентичными по структуре
   * @return array
   */
  public function getCompare()
  {
    $config = isset($this->_config['changes_for_compare']) && !empty($this->_config['changes_for_compare'])
      ? $this->_config['changes_for_compare']
      : array();

    return $this->_compare != null ? $this->_compare : CompareDB::compareTwoDB($this->db_site, $this->db_base, $config);
  }

  /** Получить сравнения для определенной таблицы
   *
   * @param $tableName
   *
   * @return mixed
   */
  public function getCompareByTableName($tableName)
  {
    return $this->getCompare()[$tableName];
  }


  public function getColumnsByTableName($tableName, $dboAlias = 'base')
  {
    return array_keys($this->getStructureDb($dboAlias)[$tableName]->columns);
  }


  /**
   * @return array
   */
  public function getNameTablesWithDifferences()
  {
    return array_keys($this->getCompare());
  }

  /** Разрендерить
   *
   * @param bool  $useCopy      - скопировать данные при создании таблицы.
   * @param array $tablesCopy   - массив названий таблиц у которых нужно копировать данные,
   *                            если массив пустой, то все таблицы которые создаются и будут копироваться.
   *
   * @return mixed
   */
  public function generateQuerySyncStructure($useCopy = true, $tablesCopy = array())
  {
    $query = array();
    $gQR   = new GenerateQueryDB($this->db_site);
    foreach ($this->getNameTablesWithDifferences() as $tableName) {
      $query[] = $gQR->renderCompareOnTableName($tableName, $this->getCompareByTableName($tableName));
      if (isset($this->getCompareByTableName($tableName)->create_table)
        && $useCopy
        && (empty($tablesCopy)
          || in_array($tableName, $tablesCopy))
      ) {
        if ($q = $this->generateCopyOfDataFromTable($tableName, $this->getDboByAlias(self::BASE_ALIAS_NAME))) {
          $query[] = $q;
        }
      }
    }

    return $query;
  }

  public function generateCopyOfDataFromTable($tableName, $dbo)
  {
    if ($rows = $this->getAllDataInTable($tableName, $dbo)) {
      $insert_params           = array();
      $rows                    = $this->getAllDataInTable($tableName, $dbo);
      $insert_params['fields'] = array_keys($rows[0]);
      foreach ($rows as $row) {
        $insert_params['values'][] = $this->getValuesRow($row);
      }

      return $this->insertDataInTable($tableName, $insert_params);
    }

    return '';
  }

  /** Выполнить запрос к базе данных
   *
   * @param        $request - запрос в формате sql
   * @param string $alias
   *
   * @return array|bool
   */
  public function fulfillRequestToDataBase($request, $alias = 'site')
  {
    /**
     * @var $dbo Dbo
     */
    $dbo = $this->getDboByAlias($alias);

    return $dbo->query($request, array(), false);
  }

  /** Выполнить запросы из массива
   *
   * @param $queryArray
   */
  function executingAllQuery($queryArray)
  {
    foreach ($queryArray as $key => $query) {
      $re = $this->fulfillRequestToDataBase($query);
      Debug()::dvD($query, $re ? 'OK' : 'Error');
    }
  }


  /** Сгенерить запросы к базе после приведения базы к стандартному типу
   *  Запросы генерятся на основании конфига созданного при создании класса
   * @return array - возвращаем сгенереный массив запросов к базе
   */
  public function postChangesDataTables()
  {
    $query = array();
    foreach ($this->_config['tables'] as $tableName => $actions) {
      foreach ($actions as $action => $params) {
        switch ($action) {
          case 'insert':
            $query[] = $this->insertDataInTable($tableName, $params);
            break;
          case 'replace':
            $query = array_merge($query, $this->replaceDataInTable($tableName, $params));
            break;
          case 'update':
            $query = array_merge($query, $this->updateDataInTable($tableName, $params));
            break;
          case 'add_missing':
            $query = array_merge($query, $this->addMissingDataInTable($tableName, $params));
            break;
          case 'copy':
            $query[] = $this->copyDataInTable($tableName, $params);
            break;
        }
      }
    }

    return $query;
  }

  /** Получить все данные из таблицы
   *
   * @param     $tableName - название таблицы
   * @param Dbo $dbo       - объект подключения к базе данных
   *
   * @return array|bool
   */
  public function getAllDataInTable($tableName, $dbo)
  {
    return $dbo->query('SELECT * FROM ' . $dbo->generateTableName($tableName));
  }

  private function transformRowsFromParams($rows, $params)
  {
    $arr = array();
    foreach ($rows as $row) {
      $key = array();
      foreach ($params['check_fields'] as $param) {
        $key[] = $row[$param];
      }
      $keyName       = implode('_', $key);
      $arr[$keyName] = $row;
    }

    return $arr;
  }

  private function getValuesRow($row)
  {
    $values = array();
    foreach ($row as $value) {
      $values[] = $value;
    }

    return $values;
  }

  /** Добавить пропущенные данные в таблице
   *  Сравниваются две базы и дополняются данные если этих строк не хватает
   *
   * @param $tableName
   * @param $params
   *
   * @return array
   */
  public function addMissingDataInTable($tableName, $params)
  {
    $query                   = array();
    $rows                    = $this->getAllDataInTable($tableName, $this->getDboByAlias('base'));
    $rows_base               = $this->transformRowsFromParams($rows, $params);
    $rows_site               = $this->transformRowsFromParams(
      $this->getAllDataInTable($tableName, $this->getDboByAlias('site')),
      $params
    );
    $insert_params           = array();
    $insert_params['fields'] = array_keys($rows[0]);
    $necessarily             = isset($params['necessarily']) ? $params['necessarily'] : null;
    foreach ($rows_base as $alias => $row) {
      $alias_site = $alias;
      $al         = array();
      foreach ($params['prefix'] as $namePrefix => $prefix) {
        $al[]       = $row[$namePrefix];
        $alias_site = str_replace('_' . $prefix, '', $alias);
      }
      $al = implode('|', $al);
      if (!isset($rows_site[$alias_site])) {
        $insert_params['values'][] = $this->getValuesFromRowWithChanges(
          $row,
          $insert_params['fields'],
          $params
        );
      } elseif ($necessarily !== null && in_array($al, $necessarily) && !preg_match('/' . $al . '/', $alias_site)) {
        $insert_params['values'][] = $this->getValuesRow($row);
      }
    }
    $query[] = $this->insertDataInTable($tableName, $insert_params);

    return $query;
  }

  /** Заменить всю таблицу новыми данными
   *
   * @param $tableName
   * @param $params
   *
   * @return array
   */
  public function replaceDataInTable($tableName, $params)
  {
    $query                   = array();
    $query[]                 = 'TRUNCATE TABLE ' . $this->_dbo->generateTableName($tableName);
    $rows                    = $this->getAllDataInTable($tableName, $this->getDboByAlias($params['source']));
    $insert_params           = array();
    $insert_params['fields'] = array_keys($rows[0]);
    foreach ($rows as $row) {
      $insert_params['values'][] = $this->getValuesRow($row);
    }

    $query[] = $this->insertDataInTable($tableName, $insert_params);

    return $query;
  }

  /** Генерация sql запроса для обновления строки в таблице
   *
   * @param $tableName - имя таблицы
   * @param $params    - массив значений и условий для выбора строки
   *
   * @return array
   */
  public function updateDataInTable($tableName, $params)
  {
    $query = array();
    foreach ($params as $param) {
      $query[] = 'UPDATE ' . $this->_dbo->generateTableName($tableName)
        . ' SET '
        . $param['set']
        . ($param['where'] != 'all' ? ' WHERE ' . $param['where'] : '')
        . ';';
    }

    return $query;
  }

  /** Генерация sql запроса для вставки строк в таблицу
   *
   * @param $tableName - имя таблицы
   * @param $params    - массив с полями и значениями
   *                   ['fields' => [],
   *                   'values' => []
   *                   ]
   *
   * @return string
   */
  public function insertDataInTable($tableName, $params)
  {
    $fields = '`' . implode('`, `', $params['fields']) . '`';
    $values = array();
    foreach ($params['values'] as $value) {
      $values[] = '(\'' . implode('\', \'', $value) . '\')';
    }

    return 'INSERT INTO ' . $this->_dbo->generateTableName($tableName)
      . ' (' . $fields . ')'
      . ' VALUES ' . "\n"
      . implode(', ' . "\n", $values)
      . ';';
  }

  /** Скопировать данные из таблицы
   *     'areas_sports'      => array(
   *       'copy' => array(
   *        'source'    => 'site',
   *        'tableName' => 'areas_types',
   *        'fields'    => array('title', 'sort', 'comment' => 'comments'),
   *           'rows'      => array(
   *              'key'    => 'type_id',
   *              'values' => array(1, 3)
   *            )
   *        ),
   *        'change' => array(
   *          'where' => 'all',
   *          'key'   => 'period_id',
   *          'value' => 2
   *        )
   *       )
   *     )
   *
   * @param $tableName - имя таблицы
   * @param $params    - параметры
   *
   * @return string
   */
  public function copyDataInTable($tableName, $params)
  {
    $tableNameSource = isset($params['tableName']) ? $params['tableName'] : $tableName;
    $rows            = $this->getAllDataInTable($tableNameSource, $this->getDboByAlias($params['source']));
    $fields          = $this->getColumnsByTableName($tableName);
    if (isset($params['fields']) && is_array($params['fields'])) {
      $fields = array();
      foreach ($params['fields'] as $key => $pr) {
        if (!is_numeric($key)) {
          $fields[] = $key;
        } else {
          $fields[] = $pr;
        }
      }
    }
    $values = array();
    foreach ($rows as $row) {
      if (isset($params['rows']) && !in_array($row[$params['rows']['key']], $params['rows']['values'])) {
        continue;
      }
      $values[] = $this->getValuesFromRowWithChanges($row, $fields, $params);
    }

    return $this->insertDataInTable($tableName, array('fields' => $fields, 'values' => $values));
  }

  /** Поменять значение
   *
   * @param $params
   * @param $row
   *
   * @param $fields
   *
   * @return mixed
   */
  public function getValuesFromRowWithChanges($row, $fields, $params)
  {
    $value = array();
    foreach ($fields as $field) {
      $key = isset($params['fields'][$field]) ? $params['fields'][$field] : $field;
      if (isset($params['change'])) {
        $value[] = $this->getChangeValue($key, $row, $params['change']);
      } else {
        $value[] = $row[$key];
      }
    }

    return $value;
  }

  public function getChangeValue($field, $row, $changes)
  {
    $value = $row[$field];
    foreach ($changes as $change) {
      $where = $change['where'];
      if (in_array($field, array_keys($change['set']))) {
        if ($where == 'all') {
          if (isset($change['stencil'])) {
            foreach ($change['stencil'] as $stencil => $text) {
              $value = str_replace($stencil, $text, $value);
            }
          } else {
            $value = $change['set'][$field];
          }
        } elseif (is_array($where)) {
          $check = 0;
          foreach ($where as $key => $val) {
            if ($row[$key] == $val) {
              $check++;
            }
          }
          if ($check == count($where)) {
            $value = $change['set'][$field];
          }
        }
      }
    }

    return $value;
  }

}