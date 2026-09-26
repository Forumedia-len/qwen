<?php

use AC\core\system\db\Query;


class reservation_holidays
{
  protected static array $fields;
  protected string $tableName = 'holidays';

  public function __construct()
  {
    if (empty(self::$fields)) {
      self::$fields = Query::getDB()->getFieldsTable($this->tableName);
    }
  }

  /* интерфейс */
  function initialize()
  {
  }

  function finalize()
  {
  }


  /* ПРАЗДНИКИ */
  //очистить все праздники года
  function clearHolidaysByYear($year)
  {
    $q = 'delete * from ' . Query::tableName($this->tableName) . ' where year(date) = "' . $year . '"';

    return Query::sqlQuery($q, [], false);
  }

  //скопировать все праздники из одного года в другой
  function duplicateYearHolidays($year_from, $year_to)
  {
    $q    = 'select month(date) as m, day(date) as d 
			from ' . Query::tableName($this->tableName) . '
			where year(date) = "' . $year_from . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //считываем с исходного года
      $holidays = [];
      foreach ($temp as $row) {
        $holidays[] = [$row['m'], $row['d']];
      }
      //записываем в новый год - перебираем праздники
      foreach ($holidays as $holiday) {
        //формируем дату
        $date = $year_to . '-' . $holiday[0] . '-' . $holiday[1];
        //вставляем праздник в год-получатель
        Query::sqlQuery('insert into ' . Query::tableName($this->tableName) . ' (date) values ("' . $date . '")', [], false);
      }
    }

    return true;
  }

  //проставить указанные праздники в указанный год (остальные удалить)
  public function setAllHolidaysByYear($year, $mysql_dates_array)
  {
    //удаляем
    $q = 'delete from ' . Query::tableName($this->tableName) . '
			where year(date) = "' . $year . '"';
    Query::sqlQuery($q, [], false);
    //проставляем
    $q = 'INSERT INTO ' . Query::tableName($this->tableName) . ' (date)
			VALUES ("' . join('"), ("', $mysql_dates_array) . '")';
    Query::sqlQuery($q, [], false);

    return true;
  }

  //добавить праздник
  public function insertHoliday($mysql_date, $sunday_prices, $sunday_times, &$inserted_holiday_id): bool
  {
    if (empty(Query::sqlQuery('select * from ' . Query::tableName($this->tableName) . ' where date = ?', [(string)$mysql_date]))) {
      //праздника на эту дату еще нет, добавляем
      $sunday = false;
      if ($sunday_times || $sunday_prices) {
        $sunday = true;
      }
      $params = [(string)$mysql_date, (int)$sunday];
      $keys   = ['`date`', '`sunday`'];
      $values = ['?', '?'];
      if ($this->checkField('sunday_prices')) {
        $params[] = (int)$sunday_prices;
        $keys[]   = '`sunday_prices`';
        $values[] = '?';
      }
      if ($this->checkField('sunday_times')) {
        $params[] = (int)$sunday_times;
        $keys[]   = '`sunday_times`';
        $values[] = '?';
      }
      $q = 'insert into ' . Query::tableName($this->tableName) . ' (' . implode(', ', $keys) . ') values (' . implode(', ', $values) . ')';
      if (Query::sqlQuery($q, $params, false)) {
        $inserted_holiday_id = Query::getLastId();

        return true;
      }
    }
    return false;
  }

  //удалить праздник
  public function removeHolidayById($holiday_id): bool
  {
    $q = 'delete from ' . Query::tableName($this->tableName) . '	where holiday_id = ?';

    return Query::sqlQuery($q, [(int)$holiday_id], false);
  }

  //праздники за год
  function getHolidaysByYear($year)
  {
    $holidays = [];
    //получаем строки праздников
    $q    = 'select date
			from ' . Query::tableName($this->tableName) . '
			where year(date) = "' . $year . '"';
    $temp = Query::sqlQuery($q);
    foreach ($temp as $row) {
      $holidays[] = $row['date'];
    }

    return $holidays;
  }

  //список праздников
  public function getHolidaysList($limit_start, $limit_count, &$holidays, ?int $year = null)
  {
    $holidays = [];
    $q        = 'select *
			from ' . Query::tableName($this->tableName) .
      ($year ? ' where year(date) = "' . $year . '"' : '') .
      ' order by date desc';
    //если надо делать частичную выборку
    if ($limit_count != false) {
      //$q .= ' limit ' . (($limit_start>0)?$limit_start:"0") . ', ' . $limit_count;
      $q .= ' limit ' . $limit_start . ', ' . $limit_count;
    }

    if (($temp = Query::sqlQuery($q)) && !empty($temp)) {
      //получаем строки праздников
      foreach ($temp as $row) {
        if (!isset($row['sunday_prices'])) {
          $row['sunday_prices'] = (int)$row['sunday'];
        }
        if (!isset($row['sunday_times'])) {
          $row['sunday_times'] = 0;
        }
        $holidays[] = $row;
      }
      return true;
    }
    return false;
  }

  //количество праздников
  public function getHolidaysCount(?int $year = null): int
  {
    $q = 'select count(*) as cnt from ' . Query::tableName($this->tableName) . ($year ? ' where year(date) = ?' : '');

    return Query::sqlQuery($q, [$year], true, ['onlyOne' => true])['cnt'] ?? 0;
  }

  //проверки даты на праздник
  function checkHoliday($q)
  {
    $query = 'select count(*) as cnt
			from ' . Query::tableName($this->tableName) . '
			where sunday="0" AND date = "' . (is_integer($q) ? date('Y-m-d', $q) : $q) . '"';
    $temp  = Query::sqlQuery($query);

    return $temp[0]['cnt'] > 0;
  }

  function getHolidaysByMonth($year, $month)
  {
    $result = [];
    $query  = 'select substring(date,9,2) as dt from ' . Query::tableName($this->tableName) . '
			where sunday="0" AND date >= "' . $year . '-' . $month . '-01" and date <= "' . $year . '-' . $month . '-31"';
    $temp   = Query::sqlQuery($query);
    foreach ($temp as $row) {
      $result[$row['dt']] = true;
    }

    return $result;
  }

  function getHolidaysByDateInterval($date_start, $date_finish, $asSunday = false): array
  {
    $result = [];

    $query = 'select h.date from ' . Query::tableName($this->tableName) . ' h
			where sunday="' . ($asSunday ? 1 : 0) . '" AND h.date >= DATE_FORMAT("' . $date_start . '", "%Y-%m-%d") and h.date <= DATE_FORMAT("' . $date_finish . '", "%Y-%m-%d")';
    if ($temp = Query::sqlQuery($query)) {
      foreach ($temp as $date) {
        $result[] = [$date['date'] . ' 00:00:00', $date['date'] . ' 23:59:59', 'type' => 'holiday'];
      }
    }

    return $result;
  }

  //проверки даты на праздник
  public function checkSundayHoliday($date, $typeSunday = 'prices'): bool
  {
    if ($typeSunday === 'times' && !$this->checkField('sunday_times')) {
      return false;
    }
    $params   = [1, (is_integer($date) ? date('Y-m-d', $date) : $date)];
    $whereAnd = '';
    if (in_array($typeSunday, ['prices', 'times']) && $this->checkField('sunday_' . $typeSunday)) {
      $params[] = 1;
      $whereAnd = ' and `sunday_' . $typeSunday . '`=?';
    }
    $query = 'select count(*) as cnt
			from ' . Query::tableName($this->tableName) . '
			where sunday=? AND date=?' . $whereAnd;
    if ($temp = Query::sqlQuery($query, $params, true, ['onlyOne' => true])) {
      return $temp['cnt'] > 0;

    }
    return false;
  }

  public function getYears()
  {
    $q = 'SELECT DISTINCT year(date) as year FROM ' . Query::tableName($this->tableName) . ' ORDER BY year DESC';

    $temp  = Query::sqlQuery($q);
    $years = [];
    foreach ($temp as $row) {
      $years[] = $row['year'];
    }

    return $years;
  }

  public function getHolidayById(int $holidayId): array
  {
    return Query::sqlQuery('SELECT * FROM ' . Query::tableName($this->tableName) . ' WHERE holiday_id = ?', [(int)$holidayId], true,
      ['onlyOne' => true]) ?? [];
  }

  public function activeSunday(int $holiday_id, string $sundayType, bool $state, &$sunday): bool
  {
    $holiday                          = $this->getHolidayById($holiday_id);
    $holiday['sunday_' . $sundayType] = (int)$state;
    if ($holiday['sunday_prices'] == 0 && $holiday['sunday_times'] == 0) {
      $sunday = 0;
    } else {
      $sunday = 1;
    }
    $set    = ['`sunday`=?'];
    $params = [(int)$sunday];
    if ($this->checkField('sunday_prices')) {
      $set[]    = '`sunday_prices`=?';
      $params[] = $holiday['sunday_prices'] ?? 0;
    }
    if ($this->checkField('sunday_times')) {
      $set[]    = '`sunday_times`=?';
      $params[] = $holiday['sunday_times'] ?? 0;
    }
    $params[] = (int)$holiday_id;
    return Query::sqlQuery('UPDATE ' . Query::tableName($this->tableName) . ' SET ' . implode(',', $set) . ' WHERE holiday_id=?',
      $params,
      false);
  }

  public function checkField($field): bool
  {
    return in_array($field, self::$fields);
  }
}