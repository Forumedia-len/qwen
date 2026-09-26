<?php

namespace AC\core\system\db;

use stdClass;

class CompareDB
{

  /** Сравнить две базы данных и вывести действия для приведения их к идентичности по структуре
   *
   * @param DB    $DBOne - структура базы данных которую будут изменять
   * @param DB    $DBTwo - базовая база данных по которой происходят изменения
   *
   * @param array $config
   *
   * @return array - возвращает массив сравнения
   */
  static public function compareTwoDB(DB $DBOne, DB $DBTwo, $config = array())
  {
    $compare   = array();
    $tablesOne = $DBOne->getStructure();
    $tablesTwo = $DBTwo->getStructure();
    foreach ($tablesTwo as $tableName => $table) {
      $tb_com = new stdClass();
      if (isset($tablesOne[$tableName])) {
        $update = self::compareTwoTables($tablesOne[$tableName], $table, $config);
        if ($update !== new stdClass()) {
          $tb_com->update_table = $update;
        }
      } else {
        $tb_com->create_table = array(
          'query' => $DBTwo->getDbo()->generateCreateTableAsString($tableName, $DBOne->getDbo()->getPrefixTable(), $DBOne->getDbo()->getNameDB())
        );
      }
      if ($tb_com != new stdClass()) {
        $compare[$tableName] = $tb_com;
      }
    }

    return $compare;
  }

  /** Сравнить две таблицы по их структуре
   *
   * @param       $tableOne
   * @param       $tableTwo
   *
   * @param array $config
   *
   * @return stdClass
   */
  static public function compareTwoTablesOld($tableOne, $tableTwo, $config = array())
  {
    $update = new stdClass();
    self::compareTableParams($tableOne, $tableTwo, $update);
    self::deleteColumn($tableOne, $tableTwo, $config, $update);
    $site_columns = $tableOne->columns;
    foreach ($tableTwo->columns as $columnName => $column) {
      $columns       = new stdClass();
      $replaceColumn = array();
      if (isset($config['replaceColumn'])) {
        if (isset($config['replaceColumn']['all']) && isset($config['replaceColumn']['all'][$columnName])) {
          foreach ($config['replaceColumn']['all'][$columnName] as $checkColumns) {
            if(in_array($checkColumns, array_keys($site_columns))) {
              $replaceColumn = $config['replaceColumn']['all'][$columnName];
            }
          }
        }
        if (isset($config['replaceColumn'][$tableTwo->table_name]) && isset($config['replaceColumn'][$tableTwo->table_name][$columnName])) {
          foreach ($config['replaceColumn']['all'][$columnName] as $checkColumns) {
            if(in_array($checkColumns, array_keys($site_columns))) {
              $replaceColumn = $config['replaceColumn'][$tableTwo->table_name][$columnName];
            }
          }
        }
      }
      if (isset($site_columns[$columnName])) {
        $tmp = self::compareColumnParam($column, $site_columns[$columnName]);
        if (!empty($tmp)) {
          $columns->update_columns = $tmp;
        }
      } elseif (!empty($replaceColumn)) {
        foreach ($replaceColumn as $oldColumnName) {
          if (isset($site_columns[$oldColumnName])) {
            $tmp = self::compareColumnParam($column, $site_columns[$oldColumnName]);
            if (!empty($tmp)) {
              $columns->update_columns = $tmp;
              $columns->params['site'] = $site_columns[$oldColumnName];
            }
          }
        }
      } else {
        $columns->create_columns        = $column;
        $columns->create_columns->after = self::findPrevColumn($tableTwo, $columnName);
      }
      if ($columns != new stdClass()) {
        if (isset($columns->update_columns) && in_array('column_key', $columns->update_columns)) {
          $column->primaryKeys = array_keys($tableTwo->primaryKeys);
        }
        $column->primaryKeys          = array_keys($tableTwo->primaryKeys);
        $columns->params              = array(
          'base' => $column,
          'site' => isset($columns->params['site'])
            ? $columns->params['site']
            : (isset($site_columns[$columnName]) ? $site_columns[$columnName]
              : null)
        );
        $update->columns[$columnName] = $columns;
      }
    }
    if ($update != new stdClass()) {
      $update->delPrimaryKeys = !empty($tableOne->primaryKeys);
    }

    return $update;
  }

  /**
   * Сравнить две таблицы и дополнительно найти недостающие индексы в $tableOne относительно $tableTwo.
   *
   * @param mixed $tableOne структуру "куда приводим"
   * @param mixed $tableTwo структуру "эталон"
   */
  static public function compareTwoTables($tableOne, $tableTwo, $config = array())
  {
    $update = new stdClass();
    self::compareTableParams($tableOne, $tableTwo, $update);
    self::deleteColumn($tableOne, $tableTwo, $config, $update);

    $site_columns = $tableOne->columns;
    foreach ($tableTwo->columns as $columnName => $column) {
      $columns       = new stdClass();
      $replaceColumn = array();
      if (isset($config['replaceColumn'])) {
        if (isset($config['replaceColumn']['all']) && isset($config['replaceColumn']['all'][$columnName])) {
          foreach ($config['replaceColumn']['all'][$columnName] as $checkColumns) {
            if(in_array($checkColumns, array_keys($site_columns))) {
              $replaceColumn = $config['replaceColumn']['all'][$columnName];
            }
          }
        }
        if (isset($config['replaceColumn'][$tableTwo->table_name]) && isset($config['replaceColumn'][$tableTwo->table_name][$columnName])) {
          foreach ($config['replaceColumn']['all'][$columnName] as $checkColumns) {
            if(in_array($checkColumns, array_keys($site_columns))) {
              $replaceColumn = $config['replaceColumn'][$tableTwo->table_name][$columnName];
            }
          }
        }
      }

      if (isset($site_columns[$columnName])) {
        $tmp = self::compareColumnParam($column, $site_columns[$columnName]);
        if (!empty($tmp)) {
          $columns->update_columns = $tmp;
        }
      } elseif (!empty($replaceColumn)) {
        foreach ($replaceColumn as $oldColumnName) {
          if (isset($site_columns[$oldColumnName])) {
            $tmp = self::compareColumnParam($column, $site_columns[$oldColumnName]);
            if (!empty($tmp)) {
              $columns->update_columns = $tmp;
              $columns->params['site'] = $site_columns[$oldColumnName];
            }
          }
        }
      } else {
        $columns->create_columns        = $column;
        $columns->create_columns->after = self::findPrevColumn($tableTwo, $columnName);
      }

      if ($columns != new stdClass()) {
        if (isset($columns->update_columns) && in_array('column_key', $columns->update_columns)) {
          $column->primaryKeys = array_keys($tableTwo->primaryKeys);
        }
        $column->primaryKeys          = array_keys($tableTwo->primaryKeys);
        $columns->params              = array(
          'base' => $column,
          'site' => isset($columns->params['site'])
            ? $columns->params['site']
            : (isset($site_columns[$columnName]) ? $site_columns[$columnName]
              : null)
        );
        $update->columns[$columnName] = $columns;
      }
    }

    // Добавление индексов отдельно от изменения колонок.
    if (isset($tableOne->indexes) && isset($tableTwo->indexes)) {
      $siteIndexes = self::getIndexDefinitions($tableOne);
      $baseIndexes = self::getIndexDefinitions($tableTwo);

      $siteSignatures = [];
      foreach ($siteIndexes as $idx) {
        $siteSignatures[self::buildIndexSignature($idx)] = true;
      }

      $indexesToAdd = [];
      foreach ($baseIndexes as $idxName => $idx) {
        $sig = self::buildIndexSignature($idx);
        if (!isset($siteSignatures[$sig])) {
          $indexesToAdd[] = $idx;
        }
      }

      if (!empty($indexesToAdd)) {
        $update->indexes_add = $indexesToAdd;
      }
    }

    if ($update != new stdClass()) {
      $update->delPrimaryKeys = !empty($tableOne->primaryKeys);
    }

    return $update;
  }

  static public function compareColumnParam($column, $site_column)
  {
    $update_columns = array();
    foreach ($column as $paramColumnName => $paramColumnValue) {
      if ($paramColumnName === 'column_key' && $paramColumnValue !== 'PRI') {
        // Для не-PRIMARY индексов расхождения обрабатываются отдельным блоком через SHOW INDEX.
        continue;
      }
      if (isset($site_column->{$paramColumnName}) && $paramColumnValue != $site_column->{$paramColumnName}) {
        if ($paramColumnName != 'ordinal_position' /*|| ($paramColumnName == 'ordinal_position' && !$create_columns)*/) {
          $update_columns[$paramColumnName] = array(
            'base' => $paramColumnValue,
            'site' => $site_column->{$paramColumnName}
          );
        }
        if($paramColumnName == 'ordinal_position' && $paramColumnValue != $site_column->{$paramColumnName} && $paramColumnValue === 1) {
          $update_columns[$paramColumnName] = array(
            'base' => $paramColumnValue,
            'site' => $site_column->{$paramColumnName}
          );
        }
      }
    }

    return $update_columns;
  }

  /**
   * Выгрузить определения индексов таблицы, пригодные для генерации ADD INDEX.
   *
   * @return array<string, array> map: index_name => def
   */
  private static function getIndexDefinitions($table): array
  {
    if (!isset($table->indexes) || !is_array($table->indexes)) {
      return [];
    }

    if (isset($table->indexes['__by_name']) && is_array($table->indexes['__by_name'])) {
      $indexes = $table->indexes['__by_name'];
      unset($indexes['PRIMARY']);
      return $indexes;
    }

    // Fallback для старого формата indexes (по колонке -> одна строка SHOW INDEX).
    $indexes = [];
    foreach ($table->indexes as $row) {
      if (!is_array($row)) {
        continue;
      }
      $keyName = $row['Key_name'] ?? '';
      if ($keyName === '' || $keyName === 'PRIMARY') {
        continue;
      }

      if (!isset($indexes[$keyName])) {
        $indexes[$keyName] = [
          'name' => $keyName,
          'unique' => ((int)($row['Non_unique'] ?? 1) === 0),
          'index_type' => strtoupper($row['Index_type'] ?? 'BTREE'),
          'columns' => []
        ];
      }

      $seq = (int)($row['Seq_in_index'] ?? 0);
      if ($seq <= 0) {
        $seq = count($indexes[$keyName]['columns']) + 1;
      }

      $colName = $row['Column_name'] ?? '';
      if ($colName === '') {
        continue;
      }

      $subPart = $row['Sub_part'] ?? null;
      $indexes[$keyName]['columns'][$seq] = [
        'name' => $colName,
        'sub_part' => ($subPart !== null && $subPart !== '' && (int)$subPart > 0) ? (int)$subPart : null,
      ];
    }

    foreach ($indexes as &$idx) {
      ksort($idx['columns']);
      $idx['columns'] = array_values($idx['columns']);
    }

    return $indexes;
  }

  private static function buildIndexSignature(array $idx): string
  {
    $cols = [];
    foreach ($idx['columns'] as $c) {
      $cols[] = $c['name'] . ($c['sub_part'] ? ('(' . $c['sub_part'] . ')') : '');
    }

    return ($idx['unique'] ? 'U' : 'N') . '|' . ($idx['index_type'] ?? 'BTREE') . '|' . implode(',', $cols);
  }

  static public function findPrevColumn($table, $columnName)
  {
    $columns = array_keys($table->columns);
    $flip    = array_flip($columns);
    $pos     = $flip[$columnName] - 1;
    if ($pos < 0) {
      $pos = 0;
    }

    return $columns[$pos];
  }

  static public function compareTableParams($tableOne, $tableTwo, &$update)
  {
    $need_update_table = array();
    foreach (array('engine', 'table_comment', 'table_collation') as $tb_field) {
      if ($tableTwo->{$tb_field} != $tableOne->{$tb_field}) {
        $need_update_table[$tb_field] = $tableTwo->{$tb_field};
      }
    }
    if (!empty($need_update_table)) {
      $update->params = $need_update_table;
    }
  }

  static public function replaceColumn()
  {
    if (isset($config['replaceColumn'])) {
      if (isset($config['replaceColumn']['all']) && isset($config['replaceColumn']['all'][$columnName])) {
        $replaceColumn = $config['replaceColumn']['all'][$columnName];
      }
      if (isset($config['replaceColumn'][$tableTwo->table_name]) && isset($config['replaceColumn'][$tableTwo->table_name][$columnName])) {
        $replaceColumn = $config['replaceColumn'][$tableTwo->table_name][$columnName];
      }
    }
  }

  static public function deleteColumn($tableOne, $tableTwo, $config, &$update)
  {
    foreach ($tableOne->columns as $columnName => $column) {
      if (isset($config['dropColumn'])
        && !isset($tableTwo->columns[$columnName])
        && ($config['dropColumn'] === true
          || (is_array($config['dropColumn'])
            && ((!isset($config['dropColumn'][$tableOne->table_name]) && in_array($tableOne->table_name, $config['dropColumn']))
              || (isset($config['dropColumn'][$tableOne->table_name])
                && (is_array($config['dropColumn'][$tableOne->table_name])
                  && in_array($columnName, $config['dropColumn'][$tableOne->table_name]))))))

      ) {
        $update->columns[$columnName] = (object)array('delete_columns' => $column);
      }
    }
  }

}