<?php

namespace AC\core\engines;

use AC\core\system\object\BaseObject;
use AC\core\system\db\Query;

class SportsEngine
{
  
  /** Получить данные по спорту по его ид
   *
   * @param $sport_id
   *
   * @return BaseObject|false
   */
  public function getSportById($sport_id)
  {
    $query = 'SELECT * FROM ' . Query::tableName('areas_sports') . ' where sport_id =' . $sport_id;
    $temp  = Query::sqlQuery($query);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $row['title'] = lang('sport_title_' . $row['alias'], 'sports_titles', [], $row['title']);
        return (object)$row;
      }
    }
    
    return false;
  }
  
  /**
   *  Данные обо всех видах спорта
   */
  public function getAllSportsData()
  {
    $result = [];
    $query  = "SELECT * FROM " . Query::tableName('areas_sports');
    $result = Query::sqlQuery($query);
    
    return $result;
  }
  
  /**
   * @param      $type_id - тип площадки
   *
   * @param bool $sport_id
   *
   * @return array|BaseObject  возращает массив объектов $sport_id, если задан sport_id выдает обект для этого id
   */
  public function getSportsTitlesByType($type_id, $sport_id = false, $onlyActive = true)
  {
    $sports_data = [];
    $query       = 'select distinct asp.title, asp.*
        from ' . Query::tableName('areas_sports') . ' as asp 
        left join ' . Query::tableName('areas') . ' as a on a.sport_id = asp.sport_id
        where a.type_id = ' . $type_id . ($sport_id ? ' and a.sport_id=' . $sport_id : '')
      . ($onlyActive ? ' and a.active = "1"' : '')
      . ' order by asp.sort';
    
    $temp = Query::sqlQuery($query);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $row['title'] = lang('sport_title_' . $row['alias'], 'sports_titles', [], $row['title']);
        $sports_data[] = (object)$row;
      }
    }
    if ($sport_id) {
      $sports_data = $sports_data[0];
    }
    
    return $sports_data;
  }
  
  public function hasColor(): bool
  {
    static $hasColorColumn;
    if ($hasColorColumn === null) {
      $hasColorColumn = Query::getDB()->checkField('color', 'areas_sports');
    }
    
    return $hasColorColumn;
  }
}