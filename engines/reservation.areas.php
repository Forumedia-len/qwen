<?php

use AC\core\system\db\Query;
use AC\core\modules\holidays\entities\enums\HolidayScheduleType;
use AC\core\modules\holidays\helpers\HolidayHelper;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DataHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\TimeHelper;

class reservation_areas
{
  protected static string $groupType = 'reservation';

  public function sqlCheckGroupType($asName = ''): string
  {
    return config('typeReservation')->useOtherGroupTypes() ? ' AND ' . (!empty($asName) ? $asName . '.' : '') . '`group`=\'' . self::$groupType . '\''
      : '';
  }

  /**
   * Фильтрует площадки по признаку архивирования, если колонка поддерживается сайтом.
   *
   * @param string    $asName   Алиас таблицы.
   * @param bool|null $archived false — без архивных, true — только архивные, null — все площадки.
   */
  public function sqlCheckArchivedArea(string $asName = '', ?bool $archived = false): string
  {
    if ($archived === null) {
      return '';
    }

    if (!Query::getDB()->checkField('archived_at', 'areas')) {
      return $archived ? ' AND 1=0' : '';
    }

    return ' AND ' . (!empty($asName) ? $asName . '.' : '') . '`archived_at` IS ' . ($archived ? 'NOT NULL' : 'NULL');
  }

  public function checkGroupType($groupType): bool
  {
    return $groupType == self::$groupType;
  }

  public function setGroupType($group = 'reservation'): void
  {
    self::$groupType = $group;
  }

  /* ПЛОЩАДКИ */
  static array $areasData;

  //данные обо всех площадках
  function getAllAreasData(&$areas_data, $onlyActive = true, $allGroupTypes = false, ?bool $archived = false): bool
  {
    $areas_data = [];
    $group      = $allGroupTypes ? 'all' : self::$groupType;
    if (empty(self::$areasData[$group])) {
      $query                   = 'SELECT a.*, at.title AS type_title, asp.title AS sport_title
     , at.sort AS type_sort, MIN(atm.start) AS start, MAX(atm.finish) AS finish,
     COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT(\'weekday\', weekday, \'start\', start, \'finish\', finish))
        FROM ' . Query::tableName('areas_timetables') . '
        WHERE area_id = a.area_id), JSON_ARRAY()) AS working_hours,
      COALESCE((SELECT JSON_ARRAYAGG( JSON_OBJECT(\'season\', period_id, \'weekday\', weekday, \'start\', start, \'price\', price))
        FROM ' . Query::tableName('areas_prices') . '
        WHERE area_id = a.area_id), JSON_ARRAY()) AS prices,
      COALESCE((SELECT JSON_ARRAYAGG( JSON_OBJECT(\'period_id\', period_id, \'start\', start))
        FROM ' . Query::tableName('areas_prices_periods') . '), JSON_ARRAY()) AS seasons
			FROM ' . Query::tableName('areas') . ' a
			INNER JOIN ' . Query::tableName('areas_types') . ' at ON at.type_id = a.type_id
			INNER JOIN ' . Query::tableName('areas_sports') . ' asp ON a.sport_id = asp.sport_id
			LEFT JOIN ' . Query::tableName('areas_timetables') . ' atm ON a.area_id = atm.area_id
			WHERE a.area_id IS NOT NULL'
        . (!$allGroupTypes ? $this->sqlCheckGroupType('at') : '')
        . ' GROUP BY a.area_id ORDER BY at.sort, a.sort';
      self::$areasData[$group] = array_column(Query::sqlQuery($query), null, 'area_id');
    }
    $areasDataGroup = self::$areasData[$group] ?? [];
    if (!empty($areasDataGroup) && is_array($areasDataGroup)) {
      $hasArchivedAt = Query::getDB()->checkField('archived_at', 'areas');
      $areas_data    = array_filter(
        $areasDataGroup,
        static function ($area) use ($onlyActive, $archived, $hasArchivedAt): bool {
          if ($onlyActive && !$area['active']) {
            return false;
          }
          if ($archived === null) {
            return true;
          }

          $isArchived = $hasArchivedAt && $area['archived_at'] !== null;

          return $archived === $isArchived;
        }
      );

      return true;
    }

    return false;
  }

  public function getAreasFullData($params = []): array
  {
    $onlyActive    = $params['onlyActive'] ?? true;
    $allGroupTypes = $params['allGroupTypes'] ?? false;
    $archived      = array_key_exists('archived', $params) ? $params['archived'] : false;
    $areas_data    = [];
    if ($this->getAllAreasData($areas_data, $onlyActive, $allGroupTypes, $archived)) {
      $areas_data = DataHelper::getDataAs($areas_data, $params);
    }

    return $areas_data ?? [];
  }

  public function getAreasPrices(): array
  {
    static $prices;
    if (empty($prices)) {
      $q = 'SELECT a.area_id, pp.title, pp.period_id as season, p.weekday, SUBSTRING(p.start, 1, 5) AS start, p.price
        FROM ' . Query::tableName('areas_prices_periods') . ' pp
        LEFT JOIN ' . Query::tableName('areas_prices') . ' p ON p.period_id = pp.period_id
        LEFT JOIN ' . Query::tableName('areas') . ' a ON a.area_id = p.area_id
        ORDER BY p.weekday, p.start';

      $temp = Query::sqlQuery($q);
      foreach ($temp as $tmp) {
        $prices[$tmp['area_id']][$tmp['season']][$tmp['weekday']][$tmp['start']] = $tmp['price'];
      }
    }

    return $prices ?? [];
  }

  //названия об площадкок по type_id
  function getAreasTitlesByType($type_id, &$areas_data)
  {
    $query      = 'select a.title from ' . Query::tableName('areas') . ' a ' .
      'where a.active = "1" and a.type_id = ' . $type_id . $this->sqlCheckArchivedArea('a') . ' order by a.sort';
    $areas_data = Query::sqlQuery($query);
    if (empty($areas_data)) {
      $areas_data = null;

      return false;
    }

    return true;
  }

  //данные о площадках по type_id
  function getAreasDataByType($type_id, &$areas_data, $sport_id = false)
  {
    $query      = 'select a.* from ' . Query::tableName('areas') . ' a ' .
      'where a.active = "1" and a.type_id = ' . $type_id . ($sport_id ? ' and sport_id=' . $sport_id : '')
      . $this->sqlCheckArchivedArea('a') . ' order by a.sort';
    $areas_data = Query::sqlQuery($query);
    if (empty($areas_data)) {
      $areas_data = null;

      return false;
    }

    return true;
  }

  //данные о количестве площадок для данного типа
  function getCountCort($id)
  {
    //$q = 'select * from ' . DB_TABLE_PREFIX . 'areas where type_id = ' . $id;

    $query      = 'select * from ' . Query::tableName('areas') . ' where active = "1" and type_id = ?'
      . $this->sqlCheckArchivedArea();
    $areas_data = Query::sqlQuery($query, [$id]);
    if (empty($areas_data)) {
      return false;
    }

    return count($areas_data);
  }

  //данные о площадке по ее ID
  public function getAreaData($area_id, &$area_row): bool
  {
    $areas = [];
    if ($this->getAllAreasData($areas, false, false, null) && isset($areas[$area_id])) {
      $area_row = $areas[$area_id];

      return true;
    }
    $area_row = null;

    return false;
  }

  protected function updateAreasDataCache(int $area_id, array $fields): void
  {
    foreach (self::$areasData as &$areasGroup) {
      if (isset($areasGroup[$area_id])) {
        $areasGroup[$area_id] = array_merge($areasGroup[$area_id], $fields);
      }
    }
    unset($areasGroup);
  }

  //расписание площадки на все дни недели
  function getAreaFullTimeTable($area_id, &$timetable)
  {
    $query     = 'select weekday,substring(start,1,5) as start,substring(finish,1,5) as finish from ' . Query::tableName(
        'areas_timetables'
      ) . ' where area_id = ' . $area_id;
    $areas_row = Query::sqlQuery($query);
    if (empty($areas_row)) {
      $timetable = null;

      return false;
    }
    foreach ($areas_row as $row) {
      $timetable[$row['weekday']] = [$row['start'], $row['finish']];
    }

    return true;
  }

  //изменить
  //1 - площадка не найдена
  //2 - время совпадает
  //3 - время не стыкуется
  function changeAreaData($area_id, $title, $comment, $sort, $timetable_new, &$error_code)
  {
    $this->getAreaPrice($area_id, $_prices, $error_code);
    $error_code = 0;
    if (list ($interval, $type_id) = Query::sqlQuery(
      'select period, type_id from ' . Query::tableName('areas') . ' where area_id = :area_id',
      ['area_id' => $area_id],
      true,
      ['style' => PDO::FETCH_NUM, 'onlyOne' => true]
    )) {
      //перебираем дни недели
      foreach ($timetable_new as $number => $day_work_time) {
        if ($day_work_time[1] >= $day_work_time[2]) {
          //начальное время раньше либо равно конечному
          $error_code = 2;

          return false;
        }
        $timetable2store[$number] = [
          TimeHelper::convertMinutes2MySQLTime($day_work_time[1]),
          TimeHelper::convertMinutes2MySQLTime($day_work_time[2]),
        ];
      }
      //изменяем параметры прощадки
      Query::sqlQuery(
        'update ' . Query::tableName('areas')
        . ' set title = :title, comment = :comment, sort = :sort, workdays = :workdays 
				where area_id = :area_id',
        [
          'title'    => $title,
          'comment'  => $comment,
          'sort'     => $sort,
          'workdays' => implode(',', array_keys($timetable_new)),
          'area_id'  => $area_id,
        ]
      );
      $seasons = $this->getSeasons();
      $prices  = [];
      //проставляем время работы и удаляем устаревшие (ныне неиспользуемые) строки в БД
      for ($weekday = 0; $weekday < 7; $weekday++) {
        if (isset ($timetable2store[$weekday])) {
          if (!isset($_prices[$weekday])
            || ($_prices[$weekday][0] !== $timetable2store[$weekday][0] || $_prices[$weekday][1] !== $timetable2store[$weekday][1])) {
            Query::sqlQuery(
              'replace ' . Query::tableName('areas_timetables')
              . ' set area_id = :area_id, weekday = :weekday, start= :start, finish= :finish',
              [
                'area_id' => $area_id,
                'weekday' => $weekday,
                'start'   => $timetable2store[$weekday][0],
                'finish'  => $timetable2store[$weekday][1],
              ],
              false
            );
            $time = $timetable2store[$weekday][0];

            while (strtotime($time) < strtotime(TimeHelper::convertTime24($timetable2store[$weekday][1], false))) {
              foreach ($seasons as $season) {
                $price = 0;
                if (isset($_prices[$weekday])) {
                  if (isset($_prices[$weekday][2][$season['period_id']][$time])) {
                    $price = $_prices[$weekday][2][$season['period_id']][$time];
                  } elseif (strtotime($time) < strtotime($_prices[$weekday][0])) {
                    $price = reset($_prices[$weekday][2][$season['period_id']]);
                  } elseif (strtotime($time) >= strtotime(TimeHelper::convertTime24($_prices[$weekday][1], false))) {
                    $price = end($_prices[$weekday][2][$season['period_id']]);
                  }
                }
                $prices[$weekday][$season['period_id']][$time] = (float)$price;
              }
              $time = TimeHelper::convertTime24(date('H:i', strtotime($time . ' + ' . $interval . ' minutes')), false);
            }
            //удаляем цены для даного дня недели
            Query::sqlQuery(
              'delete from ' . Query::tableName('areas_prices') . ' where area_id = :area_id and weekday = :weekday',
              [
                'area_id' => $area_id,
                'weekday' => $weekday,
              ],
              false
            );
          }
        } else {
          //день нерабочий - удаляем записи о нем
          foreach (['areas_prices', 'areas_timetables'] as $tableName) {
            Query::sqlQuery(
              'delete from ' . Query::tableName($tableName) . ' where area_id = :area_id and weekday = :weekday',
              [
                'area_id' => $area_id,
                'weekday' => $weekday,
              ],
              false
            );
          }
        }
      }
      if (!empty($prices)) {
        $this->setAreaPrice($area_id, $prices);
      }

      return true;
    } else {
      //площадка не найдена
      $error_code = 1;

      return false;
    }
  }

  function changeLightPrice($area_id, $light_price, $heating_price, $net_price)
  {
    $prices = [];
    if (is_float($light_price)) {
      $prices[] = 'light_price = "' . $light_price . '"';
    }
    if (is_float($heating_price)) {
      $prices[] = 'heating_price = "' . $heating_price . '"';
    }
    if (is_float($net_price)) {
      $prices[] = 'net_price = "' . $net_price . '"';
    }
    if (empty($prices)
      || Query::sqlQuery('update ' . Query::tableName('areas') . ' set ' . implode(', ', $prices) . '  where area_id = ' . (int)$area_id, [], false)) {
      return true;
    }

    return false;
  }


  /* ОПЕРАЦИИ С ТИПАМИ ПЛОЩАДОК */

  //данные обо всех типах прощадок
  function getAreasTypesData(&$areas_types_data, $withPeriods = false)
  {
    $q    = 'select * from ' . Query::tableName('areas_types') . ' at where active = 1'
      . $this->sqlCheckGroupType('at')
      . ' order by sort';
    $temp = Query::sqlQuery($q);
    if (empty($temp)) {
      $areas_types_data = null;

      return false;
    }
    foreach ($temp as $row) {
      if ($withPeriods) {
        $row['periods'] = $this->getAreaPeriodsByTypeId($row['type_id']);
      }
      $areas_types_data[$row['type_id']] = $row;
    }

    return true;
  }

  public function getAreaPeriodsByTypeId($type_id)
  {
    $periods = [];
    $q       = 'select period from ' . Query::tableName('areas') . ' where type_id = ? group by period';

    foreach (Query::sqlQuery($q, [(int)$type_id]) as $row) {
      $periods[] = $row['period'];
    }

    return $periods;
  }

  //данные о типе по type_id
  function getTypeData($id, &$area_type_data, $by_area_id = false)
  {
    static $types;
    $area_type_data = [];
    if ($by_area_id || empty($types[$id])) {
      if ($by_area_id) {
        $q = 'select at.*,
        CONCAT(at.alias, "_", at.type_id) as type_alias_type_id,
        (select count(type_id) from ' . Query::tableName('areas_types') . ' where alias=at.alias) as type_count_alias
      from ' . Query::tableName('areas_types') . ' at
      left join ' . Query::tableName('areas') . ' a on a.type_id = at.type_id 
      where a.area_id = ?'
          . $this->sqlCheckGroupType('at');
      } else {
        $q = 'select at.*, CONCAT(at.alias, "_", at.type_id) as type_alias_type_id,
            (select count(type_id) from ' . Query::tableName('areas_types') . ' where alias=at.alias) as type_count_alias
            from ' . Query::tableName('areas_types') . ' at where at.type_id = ?';
      }
      if ($temp = Query::sqlQuery($q, [$id], true, ['onlyOne' => true])) {
        $temp['current_alias'] = $temp['type_count_alias'] > 1 ? $temp['alias'] . '_' . $temp['type_id'] : $temp['alias'];
        $area_type_data        = $temp;
        if (!$by_area_id) {
          $types[$id] = $temp;
        }
      }
    } else {
      $area_type_data = $types[$id];
    }
    if (!empty($area_type_data)) {
      return true;
    }

    return false;
  }

  //изменить комментарии типа
  function changeTypeComments($type_id, $comments)
  {
    $q   = 'update ' . Query::tableName('areas_types') . ' set comments = ? where type_id = ?';
    $res = Query::sqlQuery($q, [addslashes($comments), $type_id], false);

    return $res;
  }


  /* РАСЦЕНКИ */

  //расценки площадок по id типа
  public function getAreasPrice(
    $type_id,
    &$interval,
    &$range,
    &$result,
    &$error_code,
    $sport_id = false,
    $period = null,
    array $areaIds = []
  ) {
    $areaIds       = array_values(array_unique(array_filter(array_map('intval', $areaIds))));
    $areaIdsFilter = !empty($areaIds) ? ' and a.area_id in (' . implode(',', $areaIds) . ')' : '';

    $q = 'select count(*) as cnt, a.period from ' . Query::tableName('areas') . ' a' .
      ' where a.type_id = ' . (int)$type_id .
      ($sport_id ? ' and a.sport_id=' . (int)$sport_id : '') .
      ($period ? ' and a.period = ' . (int)$period : '') .
      $areaIdsFilter .
      ' group by a.period';
    if ($temp = Query::sqlQuery($q)) {
      $result = [];

      //interval
      $count_areas = $temp[0]['cnt'];
      $interval    = $temp[0]['period'];

      //максимальное и минимальное раб. время по всем дням недели
      $q     = 'select substring(min(at.start),1,5) as start, substring(max(at.finish),1,5) as finish from ' . Query::tableName('areas_timetables') . ' at
				left join ' . Query::tableName('areas') . ' a on a.area_id = at.area_id
				where a.type_id = ' . $type_id .
        ($sport_id ? ' and a.sport_id=' . $sport_id : '') .
        ($period ? ' and a.period = ' . $period : '') .
        $areaIdsFilter;
      $t     = Query::sqlQuery($q);
      $range = $t[0];
      if ($range['start'] !== null) {
        //хоть какие-то промежутки есть

        //пробиваем рабочее время по дням недели
        $q    = 'select weekday, substring(min(at.start),1,5) as start, substring(max(at.finish),1,5) as finish from ' . Query::tableName(
            'areas_timetables'
          ) . ' at
					left join ' . Query::tableName('areas') . ' a on a.area_id = at.area_id
					where a.type_id = ' . $type_id .
          ($sport_id ? ' and a.sport_id=' . $sport_id : '') .
          ($period ? ' and a.period = ' . $period : '') .
          $areaIdsFilter .
          ' group by at.weekday order by at.weekday';
        $temp = Query::sqlQuery($q);
        foreach ($temp ?: [] as $tmp) {
          $result[$tmp['weekday']] = [$tmp['start'], $tmp['finish'], []];
        }

        //пробиваем цены
        $q = 'SELECT pp.title, pp.period_id, p.weekday, substring(p.start,1,5) as start, p.price
				FROM ' . Query::tableName('areas_prices_periods') . ' pp
				LEFT JOIN ' . Query::tableName('areas_prices') . ' p ON p.period_id = pp.period_id
				left join ' . Query::tableName('areas') . ' a on a.area_id = p.area_id
				where a.type_id = ' . $type_id .
          ($sport_id ? ' and a.sport_id=' . $sport_id : '') .
          ($period ? ' and a.period = ' . $period : '') .
          (!empty($areaIds) ? ' and a.area_id = ' . $areaIds[0] : '') .
          ' order by p.weekday, p.start';

        $temp = Query::sqlQuery($q);
        foreach ($temp ?: [] as $tmp) {
          $result[$tmp['weekday']][2][$tmp['period_id']][$tmp['start']] = $tmp['price'];
          $result[$tmp['weekday']][3]                                   = (empty($tmp['months'])
            ? []
            : explode(
              ',',
              $tmp['months']
            ));
        }
        //ok
        $error_code = 0;

        return true;
      } else {
        //рабочее время не задано
        $result     = $range = null;
        $error_code = 2;

        return false;
      }
    } else {
      //площадки не найдены или промежутки не совпадают
      $result     = $range = null;
      $error_code = 1;

      return false;
    }
  }

  /**
   * Группы площадок с одинаковой длительностью и совместимой временной сеткой.
   *
   * @param int      $typeId
   * @param int|bool $sportId
   * @param int|null $period
   *
   * @return array<string, array>
   */
  public function getAreasPriceGroups($typeId, $sportId = false, $period = null): array
  {
    $rows = $this->getAreasPriceGroupRows($typeId, $sportId, $period);
    if (!$rows) {
      return [];
    }

    $areas = [];
    foreach ($rows as $row) {
      $areaId = (int)$row['area_id'];
      if (!isset($areas[$areaId])) {
        $areas[$areaId] = [
          'area_id'   => $areaId,
          'title'     => $row['title'],
          'period'    => (int)$row['period'],
          'timetable' => [],
        ];
      }
      if ($row['weekday'] !== null) {
        $areas[$areaId]['timetable'][(int)$row['weekday']] = [$row['start'], $row['finish']];
      }
    }

    $groupList = [];
    foreach ($areas as $area) {
      $schedule = [];
      for ($weekday = 0; $weekday < 7; $weekday++) {
        $schedule[$weekday] = $area['timetable'][$weekday] ?? [null, null];
      }

      $areaGrid   = $this->buildAreasPriceGrid($area['period'], $schedule);
      $groupIndex = null;
      foreach ($groupList as $index => $group) {
        if ($group['period'] === $area['period'] && $this->areAreasPriceGridsCompatible($group['grid'], $areaGrid)) {
          $groupIndex = $index;
          break;
        }
      }

      if ($groupIndex === null) {
        $groupList[] = [
          'period'     => $area['period'],
          'area_ids'   => [$area['area_id']],
          'titles'     => [$area['title']],
          'schedule'   => $schedule,
          'grid'       => $areaGrid,
          'interval'   => null,
          'range'      => null,
          'price'      => [],
          'error_code' => 0,
        ];
        continue;
      }

      $groupList[$groupIndex]['area_ids'][] = $area['area_id'];
      $groupList[$groupIndex]['titles'][]   = $area['title'];
      $groupList[$groupIndex]['grid']       = array_replace($groupList[$groupIndex]['grid'], $areaGrid);
      $groupList[$groupIndex]['schedule']   = $this->mergeAreasPriceSchedules(
        $groupList[$groupIndex]['schedule'],
        $schedule
      );
    }

    $groups = [];
    foreach ($groupList as $group) {
      ksort($group['grid']);
      $signature    = $group['period'] . '|' . json_encode($group['grid']);
      $groupKey     = 'group_' . substr(hash('sha256', $signature), 0, 16);
      $group['key'] = $groupKey;
      unset($group['grid']);
      $groups[$groupKey] = $group;
    }

    foreach ($groups as &$group) {
      $this->getAreasPrice(
        $typeId,
        $group['interval'],
        $group['range'],
        $group['price'],
        $group['error_code'],
        $sportId,
        $group['period'],
        $group['area_ids']
      );
    }
    unset($group);

    return $groups;
  }

  /**
   * Совместимые границы могут отличаться, если их смещение кратно периоду бронирования.
   *
   * @param array<int, array{0: string|null, 1: string|null}> $schedule
   *
   * @return array<int, array{0: int|string, 1: int|string}>
   */
  private function buildAreasPriceGrid(int $period, array $schedule): array
  {
    $grid = [];
    foreach ($schedule as $weekday => [$start, $finish]) {
      if ($start === null || $finish === null) {
        continue;
      }

      $grid[$weekday] = $period > 0
        ? [
          TimeHelper::convertMySQLTimeToMinutes($start) % $period,
          TimeHelper::convertMySQLTimeToMinutes($finish) % $period,
        ]
        : [$start, $finish];
    }

    return $grid;
  }

  /**
   * Выходной день не конфликтует с рабочим; сравниваются только общие рабочие дни.
   */
  private function areAreasPriceGridsCompatible(array $groupGrid, array $areaGrid): bool
  {
    foreach ($areaGrid as $weekday => $bounds) {
      if (isset($groupGrid[$weekday]) && $groupGrid[$weekday] !== $bounds) {
        return false;
      }
    }

    return true;
  }

  /**
   * @param array<int, array{0: string|null, 1: string|null}> $groupSchedule
   * @param array<int, array{0: string|null, 1: string|null}> $areaSchedule
   *
   * @return array<int, array{0: string|null, 1: string|null}>
   */
  private function mergeAreasPriceSchedules(array $groupSchedule, array $areaSchedule): array
  {
    foreach ($areaSchedule as $weekday => [$start, $finish]) {
      if ($start === null || $finish === null) {
        continue;
      }
      if ($groupSchedule[$weekday][0] === null || $groupSchedule[$weekday][1] === null) {
        $groupSchedule[$weekday] = [$start, $finish];
        continue;
      }

      $groupSchedule[$weekday] = [
        min($groupSchedule[$weekday][0], $start),
        max($groupSchedule[$weekday][1], $finish),
      ];
    }

    return $groupSchedule;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  protected function getAreasPriceGroupRows($typeId, $sportId = false, $period = null): array
  {
    $query = 'select a.area_id, a.title, a.period, a.sort,
        at.weekday, substring(at.start, 1, 5) as start, substring(at.finish, 1, 5) as finish
      from ' . Query::tableName('areas') . ' a
      left join ' . Query::tableName('areas_timetables') . ' at on at.area_id = a.area_id
      where a.type_id = ' . (int)$typeId .
      ($sportId ? ' and a.sport_id = ' . (int)$sportId : '') .
      ($period ? ' and a.period = ' . (int)$period : '') .
      ' order by a.sort, a.area_id, at.weekday';

    return Query::sqlQuery($query) ?: [];
  }

  /**
   * Формирует тарифы площадки по рабочим дням и воскресенью для праздников.
   *
   * @param array<int, array<int, array<string, mixed>>> $prices
   * @param array<int, bool>                             $savedWeekdays
   *
   * @return array<int, array{area_id: int, period_id: int, weekday: int, start: string, price: mixed}>
   */
  protected function buildAreaPriceRows(int $areaId, array $prices, array $savedWeekdays): array
  {
    // Праздник использует воскресный тариф даже при отсутствии обычного воскресного расписания.
    if (!empty($prices[6])) {
      $savedWeekdays[6] = true;
    }

    $rows = [];
    foreach ($savedWeekdays as $weekday => $used) {
      if (!$used || empty($prices[$weekday])) {
        continue;
      }
      foreach ($prices[$weekday] as $periodId => $priceByTime) {
        foreach ($priceByTime as $time => $price) {
          // convertTime24 трактует полночь как конец суток, но здесь время является началом слота.
          $start = in_array($time, ['00:00', '00:00:00'], true)
            ? '00:00:00'
            : TimeHelper::convertTime24($time);
          $rows[] = [
            'area_id'   => $areaId,
            'period_id' => (int)$periodId,
            'weekday'   => (int)$weekday,
            'start'     => $start,
            'price'     => $price,
          ];
        }
      }
    }

    return $rows;
  }

  //установить цены на площадки заданного типа
  //price - массив вида [день недели][время старта hh:mm[:ss]] => цена
  // меняем только те цены которые пришли должно быть все огонь

  function setAreasPrice($type_id, $prices, $sport_id = null, $period = null, array $areaIds = [])
  {
    if (!$seasons = $this->getSeasons()) {
      return false;
    }

    $areaIds       = array_values(array_unique(array_filter(array_map('intval', $areaIds))));
    $areaIdsFilter = !empty($areaIds) ? ' and area_id in (' . implode(',', $areaIds) . ')' : '';

    $q = 'select area_id, period from ' . Query::tableName('areas') .
      ' where type_id = ' . (int)$type_id .
      ($sport_id ? ' and sport_id=' . (int)$sport_id : '') .
      ($period ? ' and period=' . (int)$period : '') .
      $areaIdsFilter;
    if ($temp = Query::sqlQuery($q)) {
      $replace = [];
      foreach ($temp as $row) {
        $area_id       = $row['area_id'];
        $savedWeekdays = [];
        //рабочее время площадки по дням недели
        $q = 'select weekday,substring(start,1,5) as start,substring(finish,1,5) as finish
            from ' . Query::tableName('areas_timetables') .
          ' where area_id = ' . (int)$area_id;
        foreach (Query::sqlQuery($q) ?: [] as $row2) {
          $savedWeekdays[(int)$row2['weekday']] = true;
        }

        foreach ($this->buildAreaPriceRows((int)$area_id, $prices, $savedWeekdays) as $priceRow) {
          $replace[] = '(' . $priceRow['area_id'] . ', ' .
            $priceRow['period_id'] . ', ' .
            $priceRow['weekday'] . ', "' .
            $priceRow['start'] . '", ' .
            $priceRow['price'] . ')';
        }
      }

      if (empty($replace)) {
        return false;
      }

      return Query::sqlQuery('replace into ' . Query::tableName('areas_prices') . ' values ' . implode(', ', $replace), [], false);
    } else {
      //площадки не найдены
      return false;
    }
  }

  /* ПРОВЕРКИ */

  //рабочее ли время
  //$weekday - день недели (0 - пн)
  //$mysql_time - начало промежутка (mysql_time, hh:mm[:ss])
  public function checkWorktime(
    $area_id,
    $weekday,
    $unix_time,
    &$interval = null
  ) {
    $interval = null;
    $type_arr = Query::sqlQuery("SELECT a.`period` as period, att.`start`, att.`finish`
    FROM " . Query::tableName('areas') . " a
    left join  " . Query::tableName('areas_timetables') . " att on a.area_id = att.area_id and att.weekday = " . $weekday . "
    WHERE a.`area_id`=" . $area_id, [], true, ['onlyOne' => true]);
    $interval = $type_arr['period'] ?? null;

    return isset($type_arr['start'], $type_arr['finish'])
      && strtotime(date('H:i:s', $unix_time)) >= strtotime($type_arr['start'])
      && strtotime(date('H:i:s', $unix_time)) < strtotime($type_arr['finish']);
  }

  /** Получить цену и наценку за период для конкретного клиента
   *
   * @param $client_id
   * @param $area_id
   * @param $weekday
   * @param $unix_time
   *
   * @return array|null
   */
  public function getPricePeriodForClient($client_id, $area_id, $weekday, $unix_time): ?array
  {
    $period_id = $this->getPeriodByDate(date('Y-m-d', $unix_time));
    if ($period_id) {
      $mysql_time = date('H:i:s', $unix_time);
      $q          = 'select ap.price AS price,
       if(ce.use_time = 1, if((select price from ' . Query::tableName('config_extra_week')
        . ' where extra_id=ce.extra_id and weekday = "' . $weekday . '" and start = \'' . $mysql_time . '\') is null , 0, 
        (select price from ' . Query::tableName('config_extra_week')
        . ' where extra_id=ce.extra_id and weekday = "' . $weekday . '" and start = \'' . $mysql_time . '\')), ce.rate) as extra_price
		from ' . Query::tableName('areas_prices') . ' ap ' .
        'left join ' . Query::tableName('areas') . ' a on a.area_id = ap.area_id ' .
        (($client_id && $client_id != "guest") ? 'left join ' . Query::tableName('clients') . ' c on c.client_id = "' . $client_id . '" ' : '') .
        'join ' . Query::tableName('config_extra') . ' ce  on ce.club_state = ' . (($client_id && $client_id != "guest") ? ' c.club_state'
          : 1) . ' and ce.area_type_id = a.type_id and ce.area_sport_id = a.sport_id ' .
        'where a.area_id = ' . $area_id . ' and ap.period_id = ' . $period_id . ' and ap.weekday = ' . $weekday . ' and ap.start = \'' . $mysql_time . '\'';
      $temp       = Query::sqlQuery($q, [], true, ['onlyOne' => true]);
      if ($temp) {
        return [$temp['price'], $temp['extra_price']];
      }
    }

    return null;
  }

  //расписание по типу площадок
  function getAreasTimeTableByType($type_id, &$timetable, &$prices)
  {
    $q        = 'select period from ' . Query::tableName('areas') . ' where type_id = ?' . ' limit 1';
    $temp     = Query::sqlQuery($q, [$type_id]);
    $interval = $temp[0]['period'];
    $q        = 'select a.title as title, att.weekday as weekday, substring(att.start,1,5) as start, substring(att.finish,1,5) as finish ' .
      'from ' . Query::tableName('areas_timetables') . ' att ' .
      'left join ' . Query::tableName('areas') . ' a on a.area_id = att.area_id ' .
      'where a.type_id = ' . $type_id . ' order by a.sort, att.weekday';
    $temp     = Query::sqlQuery($q);
    if (empty($temp)) {
      return false;
    }

    //время работы по дням недели
    $timetable = [];
    $result    = [];
    foreach ($temp as $r) {
      $result[$r['title']][$r['weekday']] = [$r['start'], $r['finish']];
    }

    reset($result);

    $title = '';
    //перебираем площадки
    do {
      $title = key($result) . ', ';
      while (current($result) === next($result)) {
        $title .= key($result) . ', ';
      }
      if (!prev($result)) {
        end($result);
      }

      //перебираем рабочие дни
      $tmp = current($result);
      reset($tmp);

      $weekdays = [];
      do {
        $start = $finish = key($tmp);
        while (current($tmp) === next($tmp) && $finish + 1 == key($tmp)) {
          $finish = key($tmp);
        }
        if (!prev($tmp)) {
          end($tmp);
        }
        $tmp2       = current($tmp);
        $weekdays[] = [$start, $finish, $tmp2[0], $tmp2[1]];
      } while (next($tmp));


      $timetable[substr($title, 0, -2)] = $weekdays;
    } while (next($result));

    //цены (общие на все площадки)
    $prices = [];
    $q      = 'select weekday, substring(start,1,5) as start, price ' .
      'from ' . Query::tableName('areas_prices') . ' ap ' .
      'left join ' . Query::tableName('areas') . ' a on a.area_id = ap.area_id ' .
      'where a.type_id = ' . $type_id . ' group by weekday, ap.start order by weekday, start';
    $temp   = Query::sqlQuery($q);
    $result = [];
    foreach ($temp as $r) {
      $result[$r['weekday']][$r['start']] = $r['price'];
    }
    //перебираем дни недели
    reset($result);
    do {
      $start = $finish = key($result);
      while (current($result) === next($result) && $finish + 1 === key($result)) {
      }
      if (!prev($result)) {
        end($result);
      }

      $tmp = current($result);

      //перебираем рабочее время
      $periods = [];
      do {
        $start2 = key($tmp);

        while (current($tmp) === next($tmp)) {
        }
        if (!prev($tmp)) {
          end($tmp);
        }

        //сейчас current($tmp) - это последний элемент общего дипазона
        $periods[] = [$start2, TimeHelper::addMinutes2MySQLTime(key($tmp), $interval), current($tmp)];
      } while (next($tmp));

      $prices[] = [$start, key($result), $periods];
    } while (next($result));

    return true;
  }

//расценки площадки по id
  function getAreaPrice($area_id, &$result, &$error_code)
  {
    //максимальное и минимальное раб. время по всем дням недели
    $q     = 'select substring(min(at.start),1,5), substring(max(at.finish),1,5) from ' . Query::tableName('areas_timetables') . ' at
                left join ' . Query::tableName('areas') . ' a on a.area_id = at.area_id
                where a.area_id = ' . $area_id;
    $range = Query::sqlQuery($q);
    if (empty($range)) {
      //рабочее время не задано
      $result     = $range = null;
      $error_code = 2;

      return false;
    }
    //хоть какие-то промежутки есть

    //пробиваем рабочее время по дням недели
    $q    = 'select weekday, substring(min(at.start),1,5) as start, substring(max(at.finish),1,5) as finish 
              from ' . Query::tableName('areas_timetables') . ' at
              left join ' . Query::tableName('areas') . ' a on a.area_id = at.area_id
              where a.area_id = ' . $area_id . '
              group by at.weekday
            order by at.weekday';
    $temp = Query::sqlQuery($q);
    foreach ($temp as $tmp) {
      $result[$tmp['weekday']] = [$tmp['start'], $tmp['finish'], []];
    }
    //пробиваем цены
    $q    = 'SELECT pp.title, pp.period_id, p.weekday, substring(p.start,1,5) as start, p.price 
                FROM ' . Query::tableName('areas_prices_periods') . ' pp
                LEFT JOIN ' . Query::tableName('areas_prices') . ' p ON p.period_id = pp.period_id
                left join ' . Query::tableName('areas') . ' a on a.area_id = p.area_id where a.area_id = ' . $area_id . ' 
                order by p.weekday, p.start';
    $temp = Query::sqlQuery($q);
    foreach ($temp as $tmp) {
      $result[$tmp['weekday']][2][$tmp['period_id']][$tmp['start']] = $tmp['price'];
    }
    //ok
    $error_code = 0;

    return true;
  }

  //Сезоны
  public function getSeasons()
  {
    $q   = 'select * from ' . Query::tableName('areas_prices_periods');
    $res = Query::sqlQuery($q);
    if (empty($res)) {
      return false;
    }
    foreach ($res as $row) {
      if (!empty($row['months'])) {
        $row['months'] = explode(',', $row['months']);
      } else {
        $row['months'] = [];
      }
      $result[$row['period_id']] = $row;
    }

    return $result;
  }

  public function getSeasonsStart(): array
  {
    static $seasons;
    if (empty($seasons)) {
      if ($rows = Query::sqlQuery('select period_id, start from ' . Query::tableName('areas_prices_periods'))) {
        foreach ($rows as $row) {
          $seasons[$row['period_id']] = $row['start'];
        }
        asort($seasons);
      }
    }

    return $seasons;
  }

  function clearMonthsSeazons()
  {
    $q = 'update ' . Query::tableName('areas_prices_periods') . ' set months = ""';
    Query::sqlQuery($q, [], false);

    return true;
  }

  function changeSeazons($period_id, $months)
  {
    $q = 'update ' . Query::tableName('areas_prices_periods') . ' set 
                        months = "' . addslashes($months) . '" where period_id = ' . $period_id;
    Query::sqlQuery($q, [], false);

    return true;
  }

  function changeSeazonsData($period_id, $title, $start, $colors)
  {
    $q = 'update ' . Query::tableName('areas_prices_periods') . ' set 
                        title = "' . addslashes($title) . '", start = "' . $start . '", colors = "' . addslashes(
        $colors
      ) . '" where period_id = ' . $period_id;
    Query::sqlQuery($q, [], false);

    return true;
  }

  function getSeasonById($period_id)
  {
    $q   = 'select * from ' . Query::tableName('areas_prices_periods') . ' where period_id="' . $period_id . '"';
    $res = Query::sqlQuery($q);
    if (empty($res)) {
      return false;
    }

    return $res[0];

    return false;
  }

  function getPeriodByDate($date, $seasons = []): int
  {
    if (!empty($seasons) || ($seasons = $this->getSeasonsStart())) {
      return CalendarHelper::getSeasonByDate($date, $seasons);
    }

    return 1;
  }

  public function getPeriodByMonth($month)
  {
    $q   = 'select period_id, months from ' . Query::tableName('areas_prices_periods');
    $res = Query::sqlQuery($q);
    if (empty($res)) {
      return false;
    }
    foreach ($res as $row) {
      if (in_array($month, explode(',', $row['months']))) {
        return $row['period_id'];
      }
    }

    return false;
  }

  public function setAreaPrice($area_id, $prices)
  {
    $replace = '';
    foreach ($prices as $weekday => $seasons) {
      foreach ($seasons as $season => $times) {
        foreach ($times as $time => $price) {
          $replace .= '(' . $area_id . ', ' . $season . ', ' . $weekday . ',"' . $time . '",' . $price . '),';
        }
      }
    }
    $q = 'replace into ' . Query::tableName('areas_prices') . ' values ' . substr($replace, 0, -1);
    Query::sqlQuery($q, [], false);

    return true;
  }

  public function getPeriodByMonths($month = null, $day = '01')
  {
    foreach (Query::sqlQuery('select period_id, start from ' . Query::tableName('areas_prices_periods')) as $row) {
      $out[$row['period_id']] = $row['start'];
    }
    asort($out);
    $k1      = key($out);
    $p1[$k1] = substr(current($out), 0, 7) . '-' . $day;
    next($out);
    $k2      = key($out);
    $p2[$k2] = substr(current($out), 0, 7) . '-' . $day;;
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
      $date = strtotime('1970-' . $m . '-' . $day);
      if ($date < strtotime($p2[$k2]) && $date >= strtotime($p1[$k1])) {
        $months[$m] = $k1;
      } else {
        $months[$m] = $k2;
      }
    }

    return $month ? $months[$month] : $months;
  }

  public function getWorkTimePeriods(): array
  {
    static $workTimePeriods;
    if ($workTimePeriods === null) {
      $times = [];
      $q     = 'select at.weekday, substring(at.start,1,5), substring(at.finish,1,5), a.period, a.area_id 
        from ' . Query::tableName('areas') . ' a  
			  left join ' . Query::tableName('areas_timetables') . ' at on a.area_id = at.area_id 
			  order by at.area_id,at.weekday';
      foreach (Query::sqlQuery($q, [], true, ['style' => PDO::FETCH_NUM]) as $rowTmp) {
        $keyTime = $rowTmp[1] . '|' . $rowTmp[2] . '|' . $rowTmp[3];
        if (!isset($times[$keyTime]) && !empty($rowTmp[1]) && !empty($rowTmp[2]) && !empty($rowTmp[3])) {
          $times[$keyTime] = TimeHelper::generateArrayTimeInIncrements($rowTmp[1], $rowTmp[2], $rowTmp[3], false);
        }
        $workTimePeriods[$rowTmp[4]][$rowTmp[0]] = $times[$keyTime];
      }
    }

    return $workTimePeriods;
  }

  public function getTimePeriodsByTypeId($type_id, $sport_id, $date_start, $date_finish = null): array
  {
    $timeRows = [];
    $this->getAreasDataByType($type_id, $areas_data, $sport_id);
    $workTimePeriods = $this->getWorkTimePeriods();
    foreach (DateHelper::getWorkingDaysForPeriod($date_start, $date_finish ?? $date_start) as $day) {
      $weekdayDay = CalendarHelper::getWeekdayByUnixtime(strtotime($day));
      $weekday    = HolidayHelper::resolveWeekday($day, HolidayScheduleType::Times);
      foreach ($areas_data as $rowTmp) {
        foreach ($workTimePeriods[$rowTmp['area_id']][$weekday] ?? [] as $timeRow) {
          $next                                        = TimeHelper::addMinutes2MySQLTime($timeRow, $rowTmp['period']);
          $timeRows[$rowTmp['area_id']][$weekdayDay][] = [$timeRow, $next, (int)(strtotime($date_start . ' ' . $next) < strtotime('now'))];
        }
      }
    }

    return $timeRows;
  }

  public function active($area_id, $active = true)
  {
    $this->getAreaData($area_id, $data);
    if (Query::sqlQuery('update ' . Query::tableName('areas') . ' set active = ' . (int)$active . ' where area_id=?', [$area_id], false)) {
      $this->updateAreasDataCache((int)$area_id, ['active' => (int)$active]);
      $activeType = (int)Query::sqlQuery('select if(count(a.type_id)>0, 1, 0) as cn from ' . Query::tableName('areas')
        . ' a where a.active = "1" and type_id = ' . $data['type_id'] . $this->sqlCheckArchivedArea('a'),
        [], true, ['onlyOne' => true])['cn'];
      $this->activeType($data['type_id'], $activeType);

      return true;
    }

    return false;
  }

  public function activeType($type_id, $active = true)
  {
    return Query::sqlQuery('update ' . Query::tableName('areas_types') . ' set active = ' . (int)$active . ' where type_id=?', [$type_id], false);
  }
}
