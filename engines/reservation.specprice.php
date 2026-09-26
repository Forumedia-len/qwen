<?php

use AC\core\modules\specPrices\engines\SpecPricesTriggerConditionsEngine;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;

//проводимые акции
class reservation_specprice
{
  protected $table;
  protected $table_time;
  
  function __construct()
  {
    $this->table      = Query::tableName('reservations_specprice');
    $this->table_time = Query::tableName('reservations_specprice_timetable');
  }
  
  //добавить
  function insertSprice($sort, $code, $title, $rate, $for_all, $type_sport)
  {
    $q = 'insert into ' . $this->table . ' set 
      sort = ' . $sort . ', code = "' . $code . '", title = "' . $title . '", 
      rate = "' . $rate . '", for_all = "' . $for_all . '", type_sport = "' . $type_sport . '"';
    
    return Query::sqlQuery($q, [], false) ? (int)Query::getLastId() : null;
  }
  
  //изменить
  function changeSprice($sprice_id, $sort, $code, $title, $rate, $for_all, $type_sport)
  {
    $q = 'update ' . $this->table . ' set 
      sort = ' . $sort . ', code = "' . $code . '", title = "' . $title . '", 
      rate = "' . $rate . '", for_all = "' . $for_all . '", type_sport = "' . $type_sport . '"  
      where sprice_id = ' . $sprice_id;
    
    return Query::sqlQuery($q, [], false);
  }
  
  function changeSpriceDuration($sprice_id, $duration, $start, $finish, $time)
  {
    $q = 'update ' . $this->table . ' set duration = "' . $duration . '", duration_start = "' . $start . '", duration_finish = "' . $finish . '" where sprice_id = ' . $sprice_id;
    Query::sqlQuery($q, [], false);
    if (is_array($time)) {
      Query::sqlQuery('DELETE FROM ' . $this->table_time . ' where sprice_id = ' . $sprice_id, [], false);
      $q = 'INSERT INTO ' . $this->table_time . ' (sprice_id, weekday, start, finish) VALUES ';
      $s = '';
      foreach ($time as $weekday => $t) {
        foreach ($t as $start_t => $finish_t) {
          $q .= $s . '("' . $sprice_id . '", "' . $weekday . '", "' . $start_t . '", "' . $finish_t . '")';
          $s = ', ';
        }
      }
      return Query::sqlQuery($q, [], false);
    }
    
    return false;
  }
  
  //удалить
  function removeSprice($sprice_id)
  {
    if (Query::sqlQuery('delete from ' . $this->table . ' where sprice_id = ' . $sprice_id, [], false)) {
      Query::sqlQuery('update ' . Query::tableName('reservations') . ' set sprice_id = null where sprice_id = ' . $sprice_id, [], false);
      Query::sqlQuery('delete from ' . $this->table_time . ' where sprice_id = ' . $sprice_id, [], false);
      return true;
    }
    
    return false;
  }
  
  function getSprices(&$result, $duration_time = false)
  {
    $result        = [];
    $q             = 'SELECT s.*, st.weekday, st.start, st.finish FROM ' . $this->table . ' s
									LEFT JOIN ' . $this->table_time . ' st ON s.sprice_id = st.sprice_id';
    
    if ($duration_time) {
      $unix_duration_time = strtotime($duration_time);
      
      $q .= ' WHERE s.duration="0" OR (s.duration="1"
							AND "' . date('Y-m-d', $unix_duration_time) . '">=s.duration_start
              AND "' . date('Y-m-d', $unix_duration_time) . '"<=s.duration_finish
              AND st.weekday = "' . CalendarHelper::getWeekdayByUnixtime($unix_duration_time) . '"
              AND "' . date('H:i:s', $unix_duration_time) . '">= st.start
              AND "' . date('H:i:s', $unix_duration_time) . '"< st.finish)
									GROUP BY s.sprice_id ';
    } else {
      $q .= ' order by s.sort';
    }
    $conditions = $this->getTriggerConditionsEngine()->getConditions();
    foreach (Query::sqlQuery($q) as $row) {
      if (!isset($result[$row['sprice_id']])) {
        $result[$row['sprice_id']] = [
          'sprice_id'       => $row['sprice_id'],
          'sort'            => $row['sort'],
          'code'            => $row['code'],
          'title'           => $row['title'],
          'rate'            => $row['rate'],
          'type_sport'      => $row['type_sport'],
          'for_all'         => $row['for_all'],
          'duration'        => $row['duration'],
          'duration_start'  => $row['duration_start'],
          'duration_finish' => $row['duration_finish'],
        ];
        if (isset($conditions[$row['sprice_id']])) {
          $result[$row['sprice_id']]['conditions'] = $conditions[$row['sprice_id']];
        }
      }
      if ($row['weekday'] !== null && $row['start'] && $row['finish']) {
        $result[$row['sprice_id']]['durations'][$row['weekday']][$row['start']] = $row['finish'];
      }
    }
    if (!empty($result)) {
      return true;
    }
    
    $result = null;
    
    return false;
  }
  
  public function getDuration($sprice_id, $date = null)
  {
    $result = [];
    $q      = 'select start, finish, weekday from ' . $this->table_time . ' where sprice_id=' . $sprice_id . ($date !== null
        ? ' and weekday=' . CalendarHelper::getWeekdayByUnixtime(
          strtotime($date)
        ) : '');
    $temp   = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[$row['weekday']][$row['start']] = $row['finish'];
      }
    }
    
    return $result;
  }
  
  function getSprice($sprice_id, &$result = []): bool
  {
    $q = 'select s.*, st.weekday, st.start, st.finish from ' . $this->table . ' s
								LEFT JOIN ' . $this->table_time . ' st ON s.sprice_id = st.sprice_id
								where s.sprice_id = :sprice_id';
    if ($temp = Query::sqlQuery($q, ['sprice_id' => $sprice_id])) {
      $row = $temp[0];
      unset($row['weekday'], $row['start'], $row['finish']);
      $result = $row;
      foreach ($temp as $row) {
        if ($row['weekday'] !== null && $row['start'] && $row['finish']) {
          $result['durations'][$row['weekday']][$row['start']] = $row['finish'];
        }
      }
      $result['conditions'] = $this->getTriggerConditionsEngine()->getConditionsByEntryId($sprice_id);
      
      return true;
    }
    
    $result = [];
    
    return false;
  }
  
  function checkSpriceExists($sprice_id): bool
  {
    $temp = Query::sqlQuery('select count(*) as cnt from ' . $this->table . ' where sprice_id = ' . $sprice_id);
    
    return $temp[0]['cnt'] > 0;
  }
  
  public function getTriggerConditionsEngine(): SpecPricesTriggerConditionsEngine
  {
    return getEngine('SpecPricesTriggerConditions', false);
  }
  
  public function sqlCheckDoNotShowOnAccount($alias = 'r'): string
  {
    // @todo: Если будет доработка на возможность нескольких спец цен (что нонсенс) то нужно доработать проверку и из таблицы reservation_data
    $alias = $alias ? $alias . '.' : '';
    $entry_ids = $this->getTriggerConditionsEngine()->getEntryIdsByConditionName('do_not_show_on_account');
    
    return !empty($entry_ids) ? " AND ({$alias}sprice_id NOT IN (" . implode(', ', $entry_ids). ") OR {$alias}sprice_id IS NULL)" : '';
  }
}