<?php

namespace AC\core\system\db;

use stdClass;

class StructureDB
{
  /**
   * @return array
   */
  public static function parseDbStructure(Dbo $dbo)
  {
    $structure = array();
    $fields    = array('TABLE_NAME', 'ENGINE', 'AUTO_INCREMENT', 'CREATE_OPTIONS', 'TABLE_COMMENT', 'TABLE_COLLATION');
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
        $tbl->columns[$column->COLUMN_NAME] = $columns;
        if ($column->EXTRA === 'auto_increment') {
          $tbl->primaryKey = $column->COLUMN_NAME;
        }
      }
      $tbl->primaryKey       = isset($tbl->primaryKey) ? $tbl->primaryKey : null;
      if(defined('USE_FULL_STRUCTURE') && USE_FULL_STRUCTURE) {
        $tbl->indexes          = $dbo->getIndexes($tableName);
        $tbl->primaryKeys      = $dbo->getPrimaryKeys($tableName);
        $tbl->foreignKeys      = $dbo->getForeignKeys($tableName);
      }
      $structure[$tableName] = $tbl;
    }

    return $structure;
  }
}