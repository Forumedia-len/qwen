<?php

use AC\core\system\db\Dbo;

class CopySiteOnNewBase
{
  const SITE_ALIAS_NAME = 'site';
  const BASE_ALIAS_NAME = 'base';
  private $_dbAlias = array('site', 'base');
  private $_data    = array();
  private $_compare;
  private $base_tables;
  private $_config;
  /**
   * @var Dbo
   */
  private $_dbo;

  public function __construct($config)
  {
    $this->_config = $config;
    $db_config     = $this->_config['db'];
    foreach ($this->_dbAlias as $alias) {
      $this->instanceDb($alias, array_merge($db_config['common'], $db_config[$alias]));
    }

    $this->_dbo = $this->{'db_' . self::SITE_ALIAS_NAME}->dbo;
  }

  public function __set($name, $value)
  {
    $this->_data[$name] = $value;
  }

  public function __get($name)
  {
    return isset($this->_data[$name]) ? $this->_data[$name] : null;
  }


  public function instanceDb($alias, $config)
  {
    $inst         = new stdClass();
    $inst->dbo    = Dbo::getInstance($config, true);
    $inst->tables = $this->getDbStructure($inst->dbo, $alias);

    $this->{'db_' . $alias} = $inst;
  }

  /**
   * @param $dbo Dbo
   *
   * @param $alias
   *
   * @return array
   */
  public function getDbStructure($dbo, $alias)
  {
    $structure = array();
    $fields    = array('TABLE_NAME', 'ENGINE', 'AUTO_INCREMENT', 'CREATE_OPTIONS', 'TABLE_COMMENT');
    foreach ($dbo->getStructureTables($fields) as $tableName => $table) {
      $tbl = new stdClass();
      foreach ($fields as $field) {
        $tbl->{mb_strtolower($field)} = $field == 'TABLE_NAME' ? $tableName : $table->{$field};
      }
      $column_fields = array(
        'COLUMN_NAME',
        'COLUMN_DEFAULT',
        'IS_NULLABLE',
        'COLUMN_TYPE',
        'EXTRA',
        'COLUMN_KEY',
        'COLUMN_COMMENT',
        'ORDINAL_POSITION'
      );
      foreach ($dbo->getStructureTable($tableName, $column_fields) as $column) {
        $columns = new stdClass();
        foreach ($column_fields as $cl_field) {
          $columns->{mb_strtolower($cl_field)} = $column->{$cl_field};
        }
        $this->base_tables[$alias][$tableName][] = $column->COLUMN_NAME;
        $tbl->columns[$column->COLUMN_NAME]      = $columns;
      }
      $structure[$tableName] = $tbl;
    }

    return $structure;
  }

  public function getCompare()
  {
    return $this->_compare != null ? $this->_compare : $this->compareDb();
  }

  private function compareDb()
  {
    $compare = array();
    $site    = $this->{'db_' . self::SITE_ALIAS_NAME};
    $base    = $this->{'db_' . self::BASE_ALIAS_NAME};
    foreach ($base->tables as $tableName => $table) {
      $tb_com = new stdClass();
      if (isset($site->tables[$tableName])) {
        $update            = new stdClass();
        $need_update_table = array();
        foreach (array('engine', 'table_comment') as $tb_field) {
          if ($table->{$tb_field} != $site->tables[$tableName]->{$tb_field}) {
            $need_update_table[$tb_field] = $table->{$tb_field};
          }
          if (!empty($need_update_table)) {
            $update->params = $need_update_table;
          }
        }
        $site_columns   = $site->tables[$tableName]->columns;
        $create_columns = false;
        foreach ($table->columns as $columnName => $column) {
          $columns = new stdClass();
          if (isset($site_columns[$columnName])) {
            foreach ($column as $paramColumnName => $paramColumnValue) {
              if ($paramColumnValue != $site_columns[$columnName]->{$paramColumnName}) {
                if ($paramColumnName != 'ordinal_position' || ($paramColumnName == 'ordinal_position' && !$create_columns)) {
                  $columns->update_columns[$paramColumnName] = array(
                    'base' => $paramColumnValue,
                    'site' => $site_columns[$columnName]->{$paramColumnName}
                  );
                }
              }
            }
          } else {
            $create_columns          = true;
            $columns->create_columns = $column;
          }
          if ($columns != new stdClass()) {
            $columns->params              = array(
              'base' => $column,
              'site' => $site_columns[$columnName]
            );
            $update->columns[$columnName] = $columns;
          }
        }
        if ($update != new stdClass()) {
          $tb_com->update_table = $update;
        }
      } else {
        $tb_com->create_table = true;
      }
      if ($tb_com != new stdClass()) {
        $compare[$tableName] = $tb_com;
      }
    }
    $this->_compare = $compare;

    return $compare;
  }

  public function generateUpdateColumns($tableName, $columnName, $params)
  {
    $base = $params['base'];
    $site = $params['site'];
    $deff = $params['differences'];

    return '
     CHANGE `' . $site->column_name . '` `' . $base->column_name . '` '
      . $base->column_type
      . (($base->column_default !== null) ? ' DEFAULT ' . $base->column_default  : '')
      . ($base->is_nullable == 'YES' ? ' NULL' : ' NOT NULL')
      . (!empty($base->column_comment) ? ' COMMENT \'' . $base->column_comment . '\'' : '');
  }

  public function generateCreateColumns($tableName, $column, $params)
  {
    $base = $params['base'];
    $site = $params['site'];
    $deff = $params['differences'];

    return '
     ADD COLUMN `' . $column . '` '
      . $base->column_type
      . (($base->column_default !== null) ? ' DEFAULT ' . $base->column_default  : '')
      . ($base->is_nullable == 'YES' ? ' NULL' : ' NOT NULL')
      . (!empty($base->column_comment) ? ' COMMENT \'' . $base->column_comment . '\'' : '')
      . ' AFTER `' . $this->findPrevColumn($tableName, $column) . '`';
  }

  public function renderCompareOnTableName($tableName)
  {
    $query = '';
    $compare_tb = $this->getCompare();
    foreach ($compare_tb[$tableName] as $action => $compare) {
      switch ($action) {
        case 'update_table':
          $query .= $this->generateUpdateTable($tableName, $compare);
          break;
        case 'create_table':
          $query .= $this->generateCreateTable($tableName);
          break;
        default:
          break;
      }
    }

    return $query;
  }

  public function generateUpdateTable($tableName, $params)
  {
    $query   = 'ALTER TABLE ' . $this->_dbo->generateTableName($tableName);
    $columns = array();
    foreach ($params->columns as $column => $actions) {
      foreach ($actions as $action => $params) {
        switch ($action) {
          case 'update_columns':
            $columns['update'][] = $this->generateUpdateColumns(
              $tableName,
              $column,
              array_merge($actions->params, array('differences' => $params))
            );
            break;
          case 'create_columns':
            $columns['create'][] = $this->generateCreateColumns(
              $tableName,
              $column,
              array_merge($actions->params, array('differences' => $params))
            );
            break;
          default:
            break;
        }
      }
    }
    if (!empty($columns)) {
      $update = isset($columns['update']) ? implode(', ', $columns['update']) : null;
      $create = isset($columns['create']) ? implode(', ', $columns['create']) : null;
      $query  .= (isset($update) ? $update : '') . (isset($update) && isset($create) ? ', ' : '') . (isset($create) ? $create : '');
    }
    $query .= ';';

    return $query;
  }

  public function generateCreateTable($tableName)
  {
    /** @var $dbo Dbo */
    $dbo = $this->{'db_' . self::BASE_ALIAS_NAME}->dbo;
    $tb = $dbo->generateCreateTableAsString($tableName);
    return str_replace(
        $dbo->getPrefixTable(),
        $this->_dbo->getPrefixTable(),
        $tb[0]['Create Table']
      ) . ';';
  }

  public function findPrevColumn($tableName, $columnName)
  {
    $columns = $this->base_tables['base'][$tableName];
    $flip    = array_flip($columns);
    $pos     = $flip[$columnName] - 1;
    if ($pos < 0) {
      $pos = 0;
    }

    return $columns[$pos];
  }

  public function fulfillRequestToDataBase($request)
  {
    $this->_dbo->query($request, array(), false);
  }

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
        }
      }
    }

    return $query;
  }

  public function getAllDataInTable($tableName, $source)
  {
    /** @var $dbo Dbo */
    $dbo = $this->{'db_' . $source}->dbo;

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

  public function addMissingDataInTable($tableName, $params)
  {
    $query                   = array();
    $rows                    = $this->getAllDataInTable($tableName, 'base');
    $rows_base               = $this->transformRowsFromParams($rows, $params);
    $rows_site               = $this->transformRowsFromParams($this->getAllDataInTable($tableName, 'site'), $params);
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
        $insert_params['values'][] = $this->getValuesRow($row);
      }
      if ($necessarily !== null && in_array($al, $necessarily)) {
        $insert_params['values'][] = $this->getValuesRow($row);
      }
    }

    $query[] = $this->insertDataInTable($tableName, $insert_params);

    return $query;
  }

  public function replaceDataInTable($tableName, $params)
  {
    $query                   = array();
    $query[]                 = 'TRUNCATE TABLE ' . $this->_dbo->generateTableName($tableName);
    $rows                    = $this->getAllDataInTable($tableName, $params['source']);
    $insert_params           = array();
    $insert_params['fields'] = array_keys($rows[0]);
    foreach ($rows as $row) {
      $insert_params['values'][] = $this->getValuesRow($row);
    }

    $query[] = $this->insertDataInTable($tableName, $insert_params);

    return $query;
  }

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

  public function insertDataInTable($tableName, $params)
  {
    $fields = '`' . implode('`, `', $params['fields']) . '`';
    $values = array();
    foreach ($params['values'] as $value) {
      $values[] = '(\'' . implode('\', \'', $value) . '\')';
    }

    return 'INSERT INTO ' . $this->_dbo->generateTableName($tableName)
      . ' (' . $fields . ')'
      . ' VALUES '
      . implode(', ', $values)
      . ';';
  }

}