<?php

namespace AC\core\system\db;

class QueryBilder
{
  private $tableAlias = [];

  public function select()
  {
  }

  public function fieldNameWithTableAlias($fieldName, $table, $asName = null)
  {
    return $this->getTableAlias($table) . $fieldName . ($asName ? ' as ' . $asName : '');
  }

  public function fieldNamesWithTableAlias($fieldNames): array
  {
    $out = [];
    foreach ($fieldNames as $table => $fields) {
      foreach ($fields as $field) {
        $asName = null;
        if (is_array($field)) {
          list($field, $asName) = $field;
        }
        $out[] = $this->fieldNameWithTableAlias($field, $table, $asName);
      }
    }

    return $out;
  }


  /**
   * @param array $tableAlias
   *
   * @return QueryBilder
   */
  public function setTableAlias(array $tableAlias): QueryBilder
  {
    $this->tableAlias = array_replace($this->tableAlias, $tableAlias);

    return $this;
  }

  public function getTableAlias($table = null)
  {
    $tableAlias = $this->tableAlias[$table] ?? ($table ? : '');

    return $tableAlias . '.';
  }
}