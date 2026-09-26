<?php

namespace AC\core\engines;


use AC\core\system\engine\BaseEngine;

class TranslateEngine extends BaseEngine
{
  public function tableName()
  {
    return 'translate_values';
  }

  public function all($conditions = [], $_order = [], $params = [])
  {
    $result = [];

    foreach (parent::all($conditions, $_order, $params) as $item) {
      $result[$item->table_name][$item->item_id][$item->item_name][$item->language] = $item->value;
    }

    return $result;
  }

  public function getTranslate($table_name, $item_id)
  {
    $all = $this->all(['table_name' => $table_name, 'item_id' => $item_id]);
    return $all[$table_name][$item_id] ?? [];
  }
}