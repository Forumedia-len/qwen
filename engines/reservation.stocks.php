<?php

use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\NumberHelper;

//проводимые акции
class reservation_stocks
{
  
  protected $tableName = 'reservations_stocks';
  
  function __construct()
  {
  }
  
  public function tableName(): string
  {
    return $this->tableName;
  }
  
  
  //добавить
  function insertStock($sort, $code, $title, $rate, $for_all, $type_sport, $only_once, $dimension, $group)
  {
    $params = [
      'sort'       => $sort,
      'code'       => $code,
      'title'      => $title,
      'rate'       => NumberHelper::float($rate),
      'for_all'    => $for_all ?? 0,
      'type_sport' => $type_sport,
      'only_once'  => $only_once ?? 0,
      'dimension'  => $dimension,
    ];
    if (Query::getDB()->checkField('group', $this->tableName())) {
      $params['group'] = is_numeric($group) && $group > 0 ? $group : null;
    }

    $q      = 'insert into ' . Query::tableName($this->tableName()) . ' set
      `sort` = :sort, `code` = :code, `title` = :title,
      `rate`= :rate, `for_all` = :for_all, `type_sport` = :type_sport,
      `only_once`= :only_once, `dimension` = :dimension'
      . (array_key_exists('group', $params) ? ', `group` = :group' : '');

    return Query::sqlQuery($q, $params, false);
  }
  
  //изменить
  function changeStock($stock_id, $sort, $code, $title, $rate, $for_all, $type_sport, $only_once, $dimension, $group)
  {
    $params = [
      'sort'       => $sort,
      'code'       => $code,
      'title'      => $title,
      'rate'       => NumberHelper::float($rate),
      'for_all'    => $for_all,
      'type_sport' => $type_sport,
      'only_once'  => $only_once,
      'dimension'  => $dimension,
    ];
    if (Query::getDB()->checkField('group', $this->tableName())) {
      $params['group'] = is_numeric($group) && $group > 0 ? $group : null;
    }
    $params['stock_id'] = $stock_id;
    $q      = 'update ' . Query::tableName($this->tableName()) . ' set
      `sort` = :sort, `code` = :code, `title` = :title,
      `rate`= :rate, `for_all` = :for_all, `type_sport` = :type_sport,
      `only_once`= :only_once, `dimension` = :dimension'
      . (array_key_exists('group', $params) ? ', `group` = :group' : '')
      . ' where stock_id = :stock_id';
    
    return Query::sqlQuery($q, $params, false);
  }
  
  function changeStockDuration($stock_id, $duration, $duration_start, $duration_finish, $time)
  {
    $q1Params = [
      'duration'        => $duration,
      'duration_start'  => $duration_start,
      'duration_finish' => $duration_finish,
      'stock_id'        => $stock_id
    ];
    $q1       = 'update ' . Query::tableName('reservations_stocks')
      . ' set `duration` = :duration, `duration_start` = :duration_start, `duration_finish` = :duration_finish where `stock_id` = :stock_id';
    if (Query::sqlQuery($q1, $q1Params, false) && is_array($time)) {
      Query::sqlQuery('DELETE FROM ' . Query::tableName('reservations_stocks_timetable') . ' where stock_id = :stock_id', ['stock_id' => $stock_id],
        false);
      $q = 'INSERT INTO ' . Query::tableName('reservations_stocks_timetable') . ' (stock_id, weekday, start, finish) VALUES ';
      $s = '';
      foreach ($time as $weekday => $t) {
        foreach ($t as $start_t => $finish_t) {
          $q .= $s . '("' . $stock_id . '", "' . $weekday . '", "' . $start_t . '", "' . $finish_t . '")';
          $s = ', ';
        }
      }
      return Query::sqlQuery($q, [], false);
    }
    
    return false;
  }
  
  //удалить
  function removeStock($stock_id)
  {
    if (Query::sqlQuery('delete from ' . Query::tableName('reservations_stocks') . ' where stock_id = ' . $stock_id, [], false)) {
      Query::sqlQuery('update ' . Query::tableName('reservations') . ' set stock_id = null where stock_id = ' . $stock_id, [], false);
      Query::sqlQuery('delete from ' . Query::tableName('reservations_stocks_timetable') . ' where stock_id = ' . $stock_id, [], false);
    }
    return true;
  }
  
  function getStocks(&$result = [], $duration_start = null, $duration_finish = null)
  {
    $result = [];
    if ($duration_start) {
      $unix_duration_time_start  = strtotime($duration_start);
      $unix_duration_time_finish = strtotime($duration_finish ?? $duration_start);
      
      $q      = 'SELECT s.* FROM ' . Query::tableName('reservations_stocks') . ' s  
									LEFT JOIN ' . Query::tableName('reservations_stocks_timetable') . ' st ON s.stock_id = st.stock_id
									WHERE s.duration="0" OR (s.duration="1" 
									AND "' . date('Y-m-d', $unix_duration_time_start) . '">=s.duration_start AND "' . date(
          'Y-m-d',
          $unix_duration_time_finish
        ) . '"<=s.duration_finish
									AND st.weekday = "' . CalendarHelper::getWeekdayByUnixtime($unix_duration_time_start) . '"
									AND "' . date('H:i:s', $unix_duration_time_start) . '">= st.start AND "' . date(
          'H:i:s',
          $unix_duration_time_finish
        ) . '"< st.finish)
									GROUP BY s.stock_id';
      $result = Query::sqlQuery($q);
    } else {
      $q = 'select s.*, st.weekday, st.start, st.finish from ' . Query::tableName('reservations_stocks') . ' s
								LEFT JOIN ' . Query::tableName('reservations_stocks_timetable') . ' st ON s.stock_id = st.stock_id
						 order by s.sort';
      foreach (Query::sqlQuery($q) as $row) {
        if (!isset($result[$row['stock_id']])) {
          $result[$row['stock_id']] = [
            'stock_id'        => $row['stock_id'],
            'sort'            => $row['sort'],
            'code'            => $row['code'],
            'title'           => $row['title'],
            'rate'            => $row['rate'],
            'type_sport'      => $row['type_sport'],
            'for_all'         => $row['for_all'],
            'duration'        => $row['duration'],
            'duration_start'  => $row['duration_start'],
            'duration_finish' => $row['duration_finish'],
            'only_once'       => $row['only_once'],
            'dimension'       => $row['dimension'],
            'group'           => $row['group'],
            'preferences'     => $row['preferences'],
          ];
        }
        if ($row['weekday'] !== null && $row['start'] && $row['finish']) {
          $result[$row['stock_id']]['durations'][$row['weekday']][$row['start']] = $row['finish'];
        }
      }
    }
    
    if (!empty($result)) {
      return true;
    }
    
    $result = [];
    
    return false;
  }
  
  function getStock($stock_id, &$result = [])
  {
    $q = 'select s.*, st.weekday, st.start, st.finish from ' . Query::tableName('reservations_stocks') . ' s
								LEFT JOIN ' . Query::tableName('reservations_stocks_timetable') . ' st ON s.stock_id = st.stock_id
								where s.stock_id = :stock_id';
    if ($temp = Query::sqlQuery($q, [':stock_id' => $stock_id])) {
      $row = $temp[0];
      unset($row['weekday'], $row['start'], $row['finish']);
      $result = $row;
      foreach ($temp as $row) {
        if ($row['weekday'] !== null && $row['start'] && $row['finish']) {
          $result['durations'][$row['weekday']][$row['start']] = $row['finish'];
        }
      }
      
      return true;
    }
    
    $result = [];
    
    return false;
  }
  
  public function getDuration($stock_id, $date = null)
  {
    $result = [];
    $q      = 'select start, finish, weekday from ' . Query::tableName(
        'reservations_stocks_timetable'
      ) . ' where stock_id=' . $stock_id . ($date !== null ? ' and weekday=' . CalendarHelper::getWeekdayByUnixtime(
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
  
  public function checkStockExists($stock_id)
  {
    $temp = Query::sqlQuery('select count(*) as cnt from ' . Query::tableName('reservations_stocks') . ' where stock_id = ' . $stock_id);
    
    return $temp[0]['cnt'] > 0;
  }
  
}

?>