<?php

use AC\core\system\db\Query;


class reservation_doorcodes
{

  /* интерфейс */
  function initialize(&$reservation)
  {
  }

  function finalize()
  {
  }


  /* ПЛОЩАДКИ */

  function getCodeData($type_id, $weekday, $sport_id = 1)
  {
    $q    = 'SELECT * FROM ' . Query::tableName(
        'doorcodes'
      ) . ' WHERE type_id="' . (int)$type_id . '" and sport_id="' . (int)$sport_id . '" AND weekdays = "' . $weekday . '" order by weekdays, time_start, code_id';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result['type_id']                                            = $row['type_id'];
        $result['sport_id']                                            = $row['sport_id'];
        $result['weekday']                                            = $row['weekdays'];
        $result['code'][date('H:i', strtotime($row['time_start']))][] = $row['code'];
      }

      return $result;
    } else {
      return false;
    }
  }

  function getAllCodeData($type_id, $sport_id = 1)
  {
    $q    = 'SELECT * FROM ' . Query::tableName(
        'doorcodes'
      ) . ' WHERE type_id="' . (int)$type_id . '" and sport_id="' . (int)$sport_id . '" order by weekdays, time_start, code_id';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[$row['type_id'] . '_' . $row['sport_id']][$row['weekdays']][date('H:i', strtotime($row['time_start']))][] = $row['code'];
      }

      return $result;
    } else {
      return false;
    }
  }

  function getClientCode($area_id, $weekday, $time_start)
  {
    $q    = 'SELECT dc.code FROM  ' . Query::tableName('areas') . ' a
					LEFT JOIN ' . Query::tableName('doorcodes') . ' dc ON dc.type_id = a.type_id and dc.sport_id = a.sport_id
					WHERE a.area_id="' . $area_id . '"  AND dc.weekdays = "' . $weekday . '" AND dc.time_start = "' . $time_start . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        if (!empty($row['code'])) {
          $result[] = $row['code'];
        }
      }
      if (is_array($result)) {
        return $result[array_rand($result)];
      }
    }

    return false;
  }

  //изменить
  function changeCodeData($type_id, $weekday, $code = array(), &$error_code = null, $sport_id = 1)
  {
    //Удаляем все старые записи для данной площадки и для данного дня недели
    $this->removeCodeData($type_id, $weekday, $sport_id);

    //Добавляем новые данные
    $qry = '';
    $s   = '';
    if (is_array($code)) {
      foreach ($code as $time => $codes) {
        if (is_array($codes)) {
          foreach ($codes as $c) {
            $qry .= $s . '(' . $type_id . ',' . $sport_id . ', "' . $weekday . '", "' . $time . '", "' . $c . '")';
            $s   = ', ';
          }
        } else {
          $error_code = 2;

          return false;
        }
      }
    } else {
      $error_code = 3;

      return false;
    }


    $q    = 'INSERT INTO ' . Query::tableName('doorcodes') . ' (type_id, sport_id, weekdays, time_start, code) VALUES ' . $qry;
    $temp = Query::sqlQuery($q, [], false);
    if ($temp) {
      return true;
    } else {
      $error_code = 1;
    }

    return false;
  }

  function removeCodeData($type_id, $weekday, $sport_id = 1)
  {
    $q    = 'DELETE FROM ' . Query::tableName(
        'doorcodes'
      ) . ' WHERE type_id = "' . $type_id . '" and sport_id="' . $sport_id . '" AND weekdays = "' . $weekday . '"';
    $temp = Query::sqlQuery($q, [], false);

    return $temp;
  }
}

?>