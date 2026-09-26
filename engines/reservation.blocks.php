<?php


use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;


class reservation_blocks
{

  /* интерфейс */
  function initialize()
  {
  }

  function finalize()
  {
  }


  /* ПРАВКА */

  //start и finish - mysqldatetime
  //1 - площадка не найдена
  //2 - промежуток некорректен
  //3 - пересечение по периодам
  function insertBlock($area_id, $datetime_start, $datetime_finish, $reason, $type_view, $use_webIo, &$error_code, &$block_id)
  {
    $error_code = 0;
    $query      = 'SELECT * FROM ' . Query::tableName('areas') . ' WHERE area_id = ' . $area_id;
    //проверка на наличие площадки
    $items = Query::sqlQuery($query);
    //if (mysql_num_rows($link) > 0) {
    if (!empty($items)) {
      //площадка есть
      //проверка на корректность даты
      if (strtotime($datetime_start) < strtotime($datetime_finish)) {
        //проверка на пересечение с другими блокировками
        //СУПЕР МЕГА ГЕНИАЛЬНОСТЬ
        $query = 'select * from ' . Query::tableName('blocks') . '
					where
						(
							"' . $datetime_start . '" = start
							or "' . $datetime_finish . '" = finish
							or ("' . $datetime_start . '" > start and "' . $datetime_start . '" < finish)
							or ("' . $datetime_finish . '" > start and "' . $datetime_finish . '" < finish)
							or (start > "' . $datetime_start . '" and start < "' . $datetime_finish . '")
						) and
						area_id = ' . $area_id;
        $items = Query::sqlQuery($query);
        if (empty($items)) {
          //добавление
          $q = 'insert into ' . Query::tableName('blocks') . '
						(area_id, start, finish, reason, type_view, use_webIo) values
						(' . $area_id . ', "' . $datetime_start . '", "' . $datetime_finish . '", "' . addslashes(
              $reason
            ) . '", "' . addslashes($type_view) . '", ' . addslashes($use_webIo) . ')';

          Query::sqlQuery($q, [], false);
          $block_id = Query::getLastId();

          return true;
        } else {
          //пересечение по периодам
          $error_code = 3;

          return false;
        }
      } else {
        //промежуток некорректнет
        $error_code = 2;

        return false;
      }
    } else {
      //площадка не найдена
      $error_code = 1;

      return false;
    }
  }

  //изменить блокировку
  //start и finish - mysqldatetime
  //2 - промежуток некорректен
  //3 - пересечение по периодам
  //4 - блокировка не найдена
  function changeBlock($block_id, $datetime_start, $datetime_finish, $reason, $type_view, $use_webIo, &$error_code)
  {
    $error_code = 0;

    //проверка на наличие блокировки
    $query = 'select area_id
			from ' . Query::tableName('blocks') . '
			where block_id = ' . $block_id;
    //проверка на наличие площадки
    $items = Query::sqlQuery($query);
    if (!empty($items)) {
      //блокировка найдена
      $block_data = $items[0];
      //проверка на корректность даты
      if (strtotime($datetime_start) < strtotime($datetime_finish)) {
        //проверка на пересечение с другими блокировками
        //СУПЕР МЕГА ГЕНИАЛЬНОСТЬ
        $s     = addslashes($datetime_start);
        $f     = addslashes($datetime_finish);
        $query = 'select * from ' . Query::tableName('blocks') . '
					where
						(
							"' . $s . '" = start
							or "' . $f . '" = finish
							or ("' . $s . '" > start and "' . $s . '" < finish)
							or ("' . $f . '" > start and "' . $f . '" < finish)
							or (start > "' . $s . '" and start < "' . $f . '")
						) and
					area_id = "' . $block_data['area_id'] . '" and
					block_id <> ' . $block_id;
        $items = Query::sqlQuery($query);
        if (empty($items)) {
          //изменение
          $q = 'update ' . Query::tableName('blocks') . '
						set start = "' . $datetime_start . '",
							finish = "' . $datetime_finish . '",
							reason =  "' . addslashes($reason) . '", 
							type_view =  "' . addslashes($type_view) . '",
							use_webIo =  ' . $use_webIo . ' 
						where block_id = ' . $block_id;
          Query::sqlQuery($q, [], false);

          return true;
        } else {
          //пересечение по периодам
          $error_code = 3;

          return false;
        }
      } else {
        //промежуток некорректнет
        $error_code = 2;

        return false;
      }
    } else {
      //блокировка НЕ найдена
      $error_code = 4;

      return false;
    }
  }

  //удалить блокировку
  function removeBlockById($block_id)
  {
    $query = 'delete from ' . Query::tableName('blocks') . ' where block_id = ' . $block_id;

    return Query::sqlQuery($query, [], false);
  }

  //удалить блокировку
  function removeBlockPeriod($block_id, $unix_start, $unix_finish)
  {
    if ($this->getBlockData($block_id, $block_data)) {
      $block1['start']  = strtotime($block_data['start']);
      $block1['finish'] = $unix_start;

      $block2['start']  = $unix_finish;
      $block2['finish'] = strtotime($block_data['finish']);

      if ($this->removeBlockById($block_id)) {
        if ($block1['start'] != $block1['finish']) {
          $this->insertBlock(
            $block_data['area_id'],
            date('Y-m-d H:i:s', $block1['start']),
            date('Y-m-d H:i:s', $block1['finish']),
            $block_data['reason'],
            $block_data['type_view'],
            $block_data['use_webIo'],
            $error_code,
            $block_id
          );
        }

        if ($block2['start'] != $block2['finish']) {
          $this->insertBlock(
            $block_data['area_id'],
            date('Y-m-d H:i:s', $block2['start']),
            date('Y-m-d H:i:s', $block2['finish']),
            $block_data['reason'],
            $block_data['type_view'],
            $block_data['use_webIo'],
            $error_code,
            $block_id
          );
        }
      }
    }

    return true;
  }


  /* ВЫДАЧА */

  //данные обо всех блокировках
  function getBlocksData(&$blocks_data, $active = null, $area_id = null)
  {
    $where = $params = [];
    if ($area_id !== null) {
      $params[] = (int)$area_id;
      $where[]  = 'b.area_id = ?';
    }
    if ($active !== null) {
      $where[]  = '? ' . ($active ? '<=' : '>') . ' b.finish';
      $params[] = date('Y-m-d H:i:s');
    }
    $query = 'SELECT b.*
			FROM ' . Query::tableName('blocks') . ' b' .
      ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
      ' ORDER BY b.start DESC';
    if ($blocks_data = Query::sqlQuery($query, $params)) {
      return true;
    } else {
      $blocks_data = null;

      return false;
    }
  }

  //данные блокировки по id
  function getBlockData($block_id, &$block_data)
  {
    $query = 'select b.*, a.title as area_title, a.sport_id, a.type_id
			from ' . Query::tableName('blocks') . ' b,
				' . Query::tableName('areas') . ' a,
				' . Query::tableName('areas_types') . ' at
			where a.area_id = b.area_id and
				a.type_id = at.type_id
				and b.block_id = ' . $block_id
      . Service::engines()->areas->sqlCheckGroupType('at');

    $blocks_data = Query::sqlQuery($query);
    if (!empty($blocks_data)) {
      //получаем строку блокировки
      $block_data = $blocks_data[0];

      return true;
    } else {
      $block_data = null;

      return false;
    }
  }


  /* ПРОВЕРКИ */

  //проверка периода на блокировку
  //mysql_date - yyyy-mm-dd
  //mysql_time - hh:mm
  //weekday - 0 = пн
  function checkAreaDateTimeBlocked($area_id, $mysql_date, $mysql_time, $weekday)
  {
    //проверяем обычные блокировки
    $q    = 'select count(*) as cnt from ' . Query::tableName('blocks') . '
			where area_id = ' . $area_id . ' and "' . $mysql_date . ' ' . $mysql_time . '" >= start and "' . $mysql_date . ' ' . $mysql_time . '" < finish ';
    $temp = Query::sqlQuery($q);
    $cnt  = $temp[0]['cnt'];
    if ($cnt == 0) {
      //проверяем периодические блокировки
      $q    = 'select count(*) as cnt from ' . Query::tableName('blocks_periodical') . '
				where area_id = ' . $area_id . ' and weekday_' . $weekday . ' = 1 and unlimited_block = 0 and 
					"' . $mysql_date . '" between date_start and date_finish and
					"' . $mysql_time . ':00" >= time_start and "' . $mysql_time . ':00" < time_finish';
      $temp = Query::sqlQuery($q);
      $cnt  = $temp[0]['cnt'];
      if ($cnt == 0) {
        //Проверка безлимитных блокировок
        $q    = 'select count(*) as cnt from ' . Query::tableName('blocks_periodical') . '
				where area_id = ' . $area_id . ' and weekday_' . $weekday . ' = 1 and unlimited_block = 1 and 
					"' . $mysql_date . '" >= date_start and
					"' . $mysql_time . ':00" >= time_start and "' . $mysql_time . ':00" < time_finish';
        $temp = Query::sqlQuery($q);
        $cnt  = $temp[0]['cnt'];

        return $cnt > 0;
      } else {
        return true;
      }
    } else {
      return true;
    }
  }

  //проверка есть ли блокировки в этом периоде
  //date_start date_finish - yyyy-mm-dd H:i:s
  //mysql_time - hh:mm
  //weekday - 0 = пн
  function checkAreaDateTimePeriodsBlocked($date_start, $date_finish, ?int $area_id = null)
  {
    $out               = [];
    $range_start_date  = strtotime(date('Y-m-d', strtotime($date_start)));
    $range_finish_date = strtotime(date('Y-m-d', strtotime($date_finish)));
    //проверяем обычные блокировки
    $q = 'select start, finish, area_id from ' . Query::tableName('blocks') . '
				WHERE (("' . $date_start . '">=start AND "' . $date_finish . '"<=finish) OR
			      ("' . $date_start . '"<=start AND "' . $date_finish . '">=finish) OR
			      ("' . $date_finish . '">= start AND "' . $date_finish . '"<= finish) OR
		    	  ("' . $date_start . '">= start AND "' . $date_start . '"<= finish))'
      . ($area_id ? ' AND area_id = ' . (int)$area_id : '');
    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        $out[$row['area_id']][] = [$row['start'], $row['finish'], 'type' => 'normal'];
      }
    }

    //проверяем периодические блокировки
    $q = 'select * from ' . Query::tableName('blocks_periodical') . '
				WHERE unlimited_block = 0 AND
			      (("' . $date_start . '">=date_start AND "' . $date_finish . '"<=date_finish) OR
			      ("' . $date_start . '"<=date_start AND "' . $date_finish . '">=date_finish) OR
			      ("' . $date_finish . '">= date_start AND "' . $date_finish . '"<= date_finish) OR
		    	  ("' . $date_start . '">= date_start AND "' . $date_start . '"<= date_finish))'
      . ($area_id ? ' AND area_id = ' . (int)$area_id : '');

    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        $row['weekdays'] = [];
        for ($i = 0; $i < 7; $i++) {
          if ($row['weekday_' . $i] == 1) {
            $row['weekdays'][$i] = $row['weekday_' . $i];
          }
          unset ($row['weekday_' . $i]);
        }

        $current_date   = max(strtotime($row['date_start']), $range_start_date);
        $db_date_finish = strtotime($row['date_finish']);

        while ($current_date <= $db_date_finish) {
          if (is_array($row['weekdays']) && in_array(
              CalendarHelper::getWeekdayByUnixtime($current_date),
              array_keys($row['weekdays'])
            ) && $current_date >= $range_start_date && $current_date <= $range_finish_date) {
            $out[$row['area_id']][] = [
              date('Y-m-d', $current_date) . ' ' . $row['time_start'],
              date('Y-m-d', $current_date) . ' ' . $row['time_finish'],
              'type' => 'periodical',
            ];
          }
          $current_date = mktime(
            0,
            0,
            0,
            date('m', $current_date),
            ((int)date('d', $current_date) + 1),
            date('Y', $current_date)
          );
        }
      }
    }
    // todo объеденить с переодическими блокировками
    $q = 'select * from ' . Query::tableName('blocks_periodical') . '
				WHERE unlimited_block = 1 AND
			      (date_start <= "' . $date_finish . '")'
      . ($area_id ? ' AND area_id = ' . (int)$area_id : '');
    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        $row['weekdays'] = [];
        for ($i = 0; $i < 7; $i++) {
          if ($row['weekday_' . $i] == 1) {
            $row['weekdays'][$i] = $row['weekday_' . $i];
          }
          unset ($row['weekday_' . $i]);
        }

        $current_date   = max(strtotime($row['date_start']), $range_start_date);
        $db_date_finish = strtotime($date_finish);

        while ($current_date <= $db_date_finish) {
          if (is_array($row['weekdays'])
            && in_array(
              CalendarHelper::getWeekdayByUnixtime($current_date),
              array_keys($row['weekdays'])
            ) && $current_date >= $range_start_date && $current_date <= $range_finish_date) {
            $out[$row['area_id']][] = [
              date('Y-m-d', $current_date) . ' ' . $row['time_start'],
              date('Y-m-d', $current_date) . ' ' . $row['time_finish'],
              'type' => 'unlimited',
            ];
          }
          $current_date = mktime(
            0,
            0,
            0,
            date('m', $current_date),
            ((int)date('d', $current_date) + 1),
            date('Y', $current_date)
          );
        }
      }
    }
    ksort($out);
    return $area_id ? ($out[$area_id] ?? []) : $out;
  }

  /* ПЕРИОДИЧЕСКИЕ БЛОКИРОВКИ */

  //добавить периодическую блокировку
  //weekdays - массив от 0 до 6, в котором стоят 1/0 в зависимости от включенности в промежуток дня недели (0 - понедельник)
  function insertPeriodicalBlock(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays,
    $reason,
    &$block_id = null,
    $use_webIo = 0
  ) {
    //проверка на наличие площадки
    $q    = 'select * from ' . Query::tableName('areas') . ' where area_id = "' . $area_id . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //площадка есть
      //проверка на корректность даты
      if (strtotime($date_start) <= strtotime($date_finish)) {
        //дата верна
        //проверка времени
        if (strtotime($time_start) < strtotime($time_finish)) {
          //время корректно
          //проверка на хотя бы один выбранный день
          $active_weekdays = 0;
          foreach ($weekdays as $value) {
            if ($value == 1) {
              $active_weekdays++;
            }
          }
          if ($active_weekdays > 0) {
            //есть выделенные дни
            $weekdays2store = '';
            for ($i = 0; $i < 7; $i++) {
              if ($weekdays[$i] != 1) {
                $weekdays2store .= ', "0"';
              } else {
                $weekdays2store .= ', "1"';
              }
            }
            //добавление
            $query = 'insert into ' . Query::tableName('blocks_periodical') . '
							(area_id, date_start, date_finish, time_start, time_finish, weekday_0, weekday_1, weekday_2, weekday_3, weekday_4, weekday_5, weekday_6, reason, use_webIo) values
							("' . $area_id . '", "' . $date_start . '", "' . $date_finish . '", "' . $time_start . '", "' . $time_finish . '"' . $weekdays2store . ', "' . addslashes(
                $reason
              ) . '", "' . addslashes($use_webIo) . '")';

            Query::sqlQuery($query, [], false);
            $block_id = Query::getLastId();

            return true;
          } else {
            //нет выделенных дней
            return false;
          }
        } else {
          //некорректное время
          return false;
        }
      } else {
        //некорректаня дата
        return false;
      }
    } else {
      return false;
    }
  }

  //изменить периодическую блокировку
  function changePeriodicalBlock($block_id, $date_start, $date_finish, $time_start, $time_finish, $weekdays, $reason, $use_webIo = 0)
  {
    //проверка на корректность даты
    if (strtotime($date_start) <= strtotime($date_finish)) {
      //дата верна
      //проверка времени
      if (strtotime($time_start) < strtotime($time_finish)) {
        //время корректно
        //проверка на хотя бы один выбранный день
        $active_weekdays = 0;
        foreach ($weekdays as $value) {
          if ($value == 1) {
            $active_weekdays++;
          }
        }
        if ($active_weekdays > 0) {
          //есть выделенные дни
          $weekdays2store = [];
          for ($i = 0; $i < 7; $i++) {
            if ($weekdays[$i] == 1) {
              $weekdays2store[$i] = '1';
            } else {
              $weekdays2store[$i] .= '0';
            }
          }

          //изменение
          $query = 'update ' . Query::tableName('blocks_periodical') . ' set
							date_start = "' . $date_start . '",
							date_finish = "' . $date_finish . '",
							time_start = "' . $time_start . '",
							time_finish = "' . $time_finish . '",
							reason = "' . addslashes($reason) . '",
							weekday_0 = "' . $weekdays2store[0] . '",
							weekday_1 = "' . $weekdays2store[1] . '",
							weekday_2 = "' . $weekdays2store[2] . '",
							weekday_3 = "' . $weekdays2store[3] . '",
							weekday_4 = "' . $weekdays2store[4] . '",
							weekday_5 = "' . $weekdays2store[5] . '",
							weekday_6 = "' . $weekdays2store[6] . '",
							use_webIo =  "' . $use_webIo . '" 
						where block_id = "' . $block_id . '"';
          Query::sqlQuery($query, [], false);

          return true;
        } else {
          //нет выделенных дней
          return false;
        }
      } else {
        //некорректное время
        return false;
      }
    } else {
      //некорректаня дата
      return false;
    }
  }

  //удалить блокировку
  function removePeriodicalBlockById($block_id)
  {
    $query = 'delete from ' . Query::tableName('blocks_periodical') . ' where block_id = "' . $block_id . '"';

    return Query::sqlQuery($query, [], false);
  }

  //удалить блокировку за определеную дату и время
  function removePeriodicalBlockPeriod($block_id, $date, $start_time, $finish_time)
  {
    if ($this->getPeriodicalBlockData($block_id, $block_data)) {
      $query = 'select b.*
        from ' . Query::tableName('blocks_periodical_remove_time') . ' b
        where b.block_id = ' . $block_id . ' 
        and b.date = \'' . $date . '\'
        and b.start = \'' . $start_time . ':00\'
        and b.finish = \'' . $finish_time . ':00\'';
      $temp  = Query::sqlQuery($query);

      if (empty($temp)) {
        $this->insertPeriodicalBlockRemoveTime($block_id, $date, $start_time, $finish_time);
      } else {
        return false;
      }
    }

    return true;
  }


  function insertPeriodicalBlockRemoveTime($block_id, $date, $start_time, $finish_time)
  {
    $query = 'insert into ' . Query::tableName('blocks_periodical_remove_time') . '
							(block_id, date, start, finish) values
							("' . $block_id . '", "' . $date . '", "' . $start_time . '", "' . $finish_time . '")';

    return Query::sqlQuery($query, [], false);
  }

  //данные обо всех блокировках здесь без учета новой функции что вычитается время из блокировки , таблица blocks_periodical_remove_time
  function getPeriodicalBlocksData(&$blocks_data, $active = null, $area_id = null)
  {
    $blocks_data = $where = $params = [];
    if ($area_id !== null) {
      $params[] = (int)$area_id;
      $where[]  = 'b.area_id = ?';
    }
    if ($active !== null) {
      $where[]  = '? ' . ($active ? '<=' : '>') . ' b.date_finish';
      $params[] = date('Y-m-d H:i:s');
    }
    $query = 'select b.*
			from ' . Query::tableName('blocks_periodical') . ' b
			where b.unlimited_block = 0' .
      ($where ? ' and ' . implode(' and ', $where) : '') .
      ' order by b.date_start desc, b.time_start desc';
    if ($temp = Query::sqlQuery($query, $params)) {
      //получаем строки прощадок
      foreach ($temp as $row) {
        $row['weekdays']    = [];
        $row['weekdays'][0] = $row['weekday_0'];
        $row['weekdays'][1] = $row['weekday_1'];
        $row['weekdays'][2] = $row['weekday_2'];
        $row['weekdays'][3] = $row['weekday_3'];
        $row['weekdays'][4] = $row['weekday_4'];
        $row['weekdays'][5] = $row['weekday_5'];
        $row['weekdays'][6] = $row['weekday_6'];
        unset ($row['weekday_0']);
        unset ($row['weekday_1']);
        unset ($row['weekday_2']);
        unset ($row['weekday_3']);
        unset ($row['weekday_4']);
        unset ($row['weekday_5']);
        unset ($row['weekday_6']);
        $blocks_data[] = $row;
      }

      return true;
    } else {
      return false;
    }
  }

  //данные  периодической блокировки по id
  function getPeriodicalBlockData($block_id, &$row)
  {
    $row = [];

    $query = 'select b.*, a.title, at.color
			from ' . Query::tableName('blocks_periodical') . ' b,
				' . Query::tableName('areas') . ' a,
				' . Query::tableName('areas_types') . ' at
			where a.area_id = b.area_id and
				a.type_id = at.type_id and
				b.unlimited_block = 0 
				and b.block_id = ' . $block_id;
    $temp  = Query::sqlQuery($query);

    if (!empty($temp)) {
      //получаем строку блокировки
      $row = $temp[0];

      $row['weekdays']    = [];
      $row['weekdays'][0] = $row['weekday_0'];
      $row['weekdays'][1] = $row['weekday_1'];
      $row['weekdays'][2] = $row['weekday_2'];
      $row['weekdays'][3] = $row['weekday_3'];
      $row['weekdays'][4] = $row['weekday_4'];
      $row['weekdays'][5] = $row['weekday_5'];
      $row['weekdays'][6] = $row['weekday_6'];
      unset ($row['weekday_0']);
      unset ($row['weekday_1']);
      unset ($row['weekday_2']);
      unset ($row['weekday_3']);
      unset ($row['weekday_4']);
      unset ($row['weekday_5']);
      unset ($row['weekday_6']);

      return true;
    } else {
      return false;
    }
  }

  /* Unlimited блокировки */

  //добавить бесконечную блокировку
  //weekdays - массив от 0 до 6, в котором стоят 1/0 в зависимости от включенности в промежуток дня недели (0 - понедельник)
  function insertUnlimitedBlock($area_id, $date_start, $time_start, $time_finish, $weekdays, $reason, &$block_id = null, $use_webIo = 0)
  {

    //проверка на наличие площадки
    $query = 'select * from ' . Query::tableName('areas') . ' where area_id = "' . $area_id . '"';
    $temp  = Query::sqlQuery($query);
    if (!empty($temp)) {
      //дата верна
      //проверка времени
      if (strtotime($time_start) < strtotime($time_finish)) {
        //время корректно
        //проверка на хотя бы один выбранный день
        $active_weekdays = 0;
        foreach ($weekdays as $value) {
          if ($value == 1) {
            $active_weekdays++;
          }
        }
        if ($active_weekdays > 0) {
          //есть выделенные дни
          $weekdays2store = '';
          for ($i = 0; $i < 7; $i++) {
            if ($weekdays[$i] != 1) {
              $weekdays2store .= ', "0"';
            } else {
              $weekdays2store .= ', "1"';
            }
          }

          $is_unlimited_block = true;
          //добавление
          $query = 'insert into ' . Query::tableName('blocks_periodical') . ' 
                        (area_id, date_start, time_start, time_finish, weekday_0, weekday_1, weekday_2, weekday_3, weekday_4, weekday_5, weekday_6, reason, unlimited_block, use_webIo) values
                        ("' . $area_id . '", "' . $date_start . '", "' . $time_start . '", "' . $time_finish . '"' . $weekdays2store . ', "' . addslashes(
              $reason
            ) . '", ' . ($is_unlimited_block ? "1" : "0") . ', "' . $use_webIo . '")';

          Query::sqlQuery($query, [], false);
          $block_id = Query::getLastId();

          return true;
        } else {
          //нет выделенных дней
          return false;
        }
      } else {
        //некорректное время
        return false;
      }
    } else {
      return false;
    }
  }

  //изменить бесконечную блокировку
  function changeUnlimitedBlock($block_id, $date_start, $time_start, $time_finish, $weekdays, $reason, $use_webIo = 0)
  {
    //дата верна
    //проверка времени
    if (strtotime($time_start) < strtotime($time_finish)) {
      //время корректно
      //проверка на хотя бы один выбранный день
      $active_weekdays = 0;
      foreach ($weekdays as $value) {
        if ($value == 1) {
          $active_weekdays++;
        }
      }
      if ($active_weekdays > 0) {
        //есть выделенные дни
        $weekdays2store = [];
        for ($i = 0; $i < 7; $i++) {
          if ($weekdays[$i] == 1) {
            $weekdays2store[$i] = '1';
          } else {
            $weekdays2store[$i] .= '0';
          }
        }

        //изменение
        $query = 'update ' . Query::tableName('blocks_periodical') . ' set
                        date_start = "' . $date_start . '",                        
                        time_start = "' . $time_start . '",
                        time_finish = "' . $time_finish . '",
                        reason = "' . addslashes($reason) . '",
                        weekday_0 = "' . $weekdays2store[0] . '",
                        weekday_1 = "' . $weekdays2store[1] . '",
                        weekday_2 = "' . $weekdays2store[2] . '",
                        weekday_3 = "' . $weekdays2store[3] . '",
                        weekday_4 = "' . $weekdays2store[4] . '",
                        weekday_5 = "' . $weekdays2store[5] . '",
                        weekday_6 = "' . $weekdays2store[6] . '",
                        use_webIo = "' . $use_webIo . '"
                    where block_id = "' . $block_id . '"';
        Query::sqlQuery($query, [], false);

        return true;
      } else {
        //нет выделенных дней
        return false;
      }
    } else {
      //некорректное время
      return false;
    }
  }

  function removeUnlimitedBlock($block_id)
  {
    $query = 'delete from ' . Query::tableName('blocks_periodical') . ' where block_id = "' . $block_id . '"';

    return Query::sqlQuery($query, [], false);
  }

  function getUnlimitedBlocksData(&$blocks_data, $active = null, $area_id = null)
  {
    $blocks_data = $where = $params = [];
    if ($area_id !== null) {
      $params[] = (int)$area_id;
      $where[]  = 'b.area_id = ?';
    }
    if ($active !== null) {
      $where[]  = '? ' . ($active ? '<=' : '>') . ' b.date_finish';
      $params[] = date('Y-m-d H:i:s');
    }

    $query = 'select b.*
			from ' . Query::tableName('blocks_periodical') . ' b
			where b.unlimited_block = 1 ' .
      ($where ? ' and ' . implode(' and ', $where) : '') .
      ' order by b.date_start desc, b.time_start desc';
    $temp  = Query::sqlQuery($query, $params);

    if (!empty($temp)) {
      //получаем строки прощадок
      foreach ($temp as $row) {
        $row['weekdays']    = [];
        $row['weekdays'][0] = $row['weekday_0'];
        $row['weekdays'][1] = $row['weekday_1'];
        $row['weekdays'][2] = $row['weekday_2'];
        $row['weekdays'][3] = $row['weekday_3'];
        $row['weekdays'][4] = $row['weekday_4'];
        $row['weekdays'][5] = $row['weekday_5'];
        $row['weekdays'][6] = $row['weekday_6'];
        unset ($row['weekday_0']);
        unset ($row['weekday_1']);
        unset ($row['weekday_2']);
        unset ($row['weekday_3']);
        unset ($row['weekday_4']);
        unset ($row['weekday_5']);
        unset ($row['weekday_6']);
        $blocks_data[] = $row;
      }

      return true;
    } else {
      return false;
    }
  }

  function getUnlimitedBlockData($block_id, &$row)
  {
    $row   = [];
    $query = 'select b.*, a.title, at.color
			from ' . Query::tableName('blocks_periodical') . ' b,
				' . Query::tableName('areas') . ' a,
				' . Query::tableName('areas_types') . ' at
			where a.area_id = b.area_id and
				a.type_id = at.type_id and
				b.unlimited_block = 1 
				and b.block_id = ' . $block_id;
    $temp  = Query::sqlQuery($query);
    if (!empty($temp)) {
      //получаем строку блокировки
      $row = $temp[0];

      $row['weekdays']    = [];
      $row['weekdays'][0] = $row['weekday_0'];
      $row['weekdays'][1] = $row['weekday_1'];
      $row['weekdays'][2] = $row['weekday_2'];
      $row['weekdays'][3] = $row['weekday_3'];
      $row['weekdays'][4] = $row['weekday_4'];
      $row['weekdays'][5] = $row['weekday_5'];
      $row['weekdays'][6] = $row['weekday_6'];
      unset ($row['weekday_0']);
      unset ($row['weekday_1']);
      unset ($row['weekday_2']);
      unset ($row['weekday_3']);
      unset ($row['weekday_4']);
      unset ($row['weekday_5']);
      unset ($row['weekday_6']);

      return true;
    } else {
      return false;
    }
  }

  public function getBlocksTypes($alias = false)
  {
    $block_types = [];
    foreach (Query::sqlQuery('select * from ' . Query::tableName('blocks_types')) as $row) {
      if ($alias) {
        $block_types[(int)$row['type_id']] = $row;
      } else {
        $block_types[(int)$row['type_id']] = $row['title'];
      }
    }

    return $block_types;
  }

  public function getPeriodicalBlocksDataOverPeriod(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays
  ) {
    $useBlocks = [];
    $out       = [];

    $query = 'select *, reason as name from ' . Query::tableName('blocks_periodical') . '
				WHERE area_id = "' . $area_id . '" AND unlimited_block = 0 and(
			      ("' . $date_finish . '">=date_start and
			      "' . $date_start . '"<=date_finish)) and time_start<="' . $time_finish . '" and time_finish>="' . $time_start . '"';
    //проверяем периодические блокировки
    foreach (Query::sqlQuery($query) as $row) {
      $row['type'] = "PeriodicalBlock";

      for ($i = 0; $i < 7; $i++) {
        if ((empty($weekdays) || ($row['weekday_' . $i] == 1 && in_array($row['weekday_' . $i], $weekdays))) && !in_array(
            $row['block_id'],
            $useBlocks
          )) {
          $useBlocks[]     = $row['block_id'];
          $row['weekdays'] = [];
          for ($j = 0; $j < 7; $j++) {
            if ($row['weekday_' . $j]) {
              $row['weekdays'][] = $j;
            }
            unset ($row['weekday_' . $j]);
          }
          $out[] = $row;
        }
      }
    }

    return $out;
  }

  public function getNormalBlocksDataOverPeriod(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish
  ) {
    $out    = [];
    $start  = $date_start . ' ' . $time_start;
    $finish = $date_finish . ' ' . $time_finish;

    $query = 'select *, reason as name, substring(start,1,10) as date_start, substring(finish,1,10) as date_finish, substring(start,12,8) as time_start, substring(finish,12,8) as time_finish from ' . Query::tableName('blocks') . '
				WHERE area_id = "' . $area_id . '" AND (
			      ("' . $finish . '">=start and
			      "' . $start . '"<=finish))';
    //проверяем периодические блокировки
    foreach (Query::sqlQuery($query) as $row) {
      $row['type'] = "NormalBlock";
      $out[]       = $row;
    }

    return $out;
  }

  public function getUnlimitedBlocksDataOverPeriod(
    $area_id,
    $date_start,
    $time_start,
    $time_finish,
    $weekdays
  ) {
    $useBlocks = [];
    $out       = [];

    $query = 'select *, reason as name from ' . Query::tableName('blocks_periodical') . '
				WHERE area_id = "' . $area_id . '" AND unlimited_block = 1 AND 
			      "' . $date_start . '">=date_start and time_start<="' . $time_finish . '" and time_finish>="' . $time_start . '"';
    //проверяем периодические блокировки
    foreach (Query::sqlQuery($query) as $row) {
      $row['type'] = "UnlimitedBlock";

      for ($i = 0; $i < 7; $i++) {
        if ((empty($weekdays) || ($row['weekday_' . $i] == 1 && in_array($row['weekday_' . $i], $weekdays))) && !in_array(
            $row['block_id'],
            $useBlocks
          )) {
          $useBlocks[]     = $row['block_id'];
          $row['weekdays'] = [];
          for ($j = 0; $j < 7; $j++) {
            if ($row['weekday_' . $j]) {
              $row['weekdays'][] = $j;
            }
            unset ($row['weekday_' . $j]);
          }

          $out[] = $row;
        }
      }
    }

    return $out;
  }
}
