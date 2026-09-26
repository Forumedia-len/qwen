<?php

namespace AC\core\engines;

use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\modules\areas\entities\dto\AreaTypeDto;
use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\areas\services\AreasPageResolver;
use AC\core\system\db\Query;
use AC\core\system\helpers\TimeHelper;
use PDO;

useClass('engines\reservation.areas');

/**
 * Class ReservationAreas Площадки
 */
class AreasEngine extends \reservation_areas
{
  private $query;
  private $tableName = 'areas';
  
  public function __construct()
  {
    $this->query = new Query();
  }
  
  /** Получить название таблицы
   * @return string
   */
  public function getTableName()
  {
    return Query::$prefix . $this->tableName;
  }
  
  //данные обо всех площадках
  public function all($onlyActive = true, ?bool $archived = false)
  {
    if ($_result = Query::sqlQuery(
      'select a.*, at.title as type_title, at.sort as type_sort, asp.title as sport_title
			from ' . Query::tableName('areas') . ' a
			left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
			left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id'
      . ' where a.area_id is not null'
      . $this->sqlCheckGroupType('at')
      . ($onlyActive ? ' and a.active = "1"' : '')
      . $this->sqlCheckArchivedArea('a', $archived)
      . ' order by at.sort, a.sort',
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'argument' => module('areas')->getFullModelName('AreasModel')]
    )) {
      $result = [];
      /** @var AreasModel $item */
      foreach ($_result as $item) {
        $result[$item->{$item->getPrimaryKey()}] = $item;
      }
      
      return $result;
    }
    
    return null;
  }
  
  public function getTitleByTypeAndSport($type_id, $sport_id, $type_title = 'title', $allGroupTypes = true)
  {
    return $this->getSportsByType(false, $allGroupTypes)[$type_id . '_' . $sport_id]->{$type_title};
  }
  
  public function getTitleByAreaId($area_id, $type_title = 'title', $addAreaTitle = false)
  {
    $this->getAreaData($area_id, $area_data);
    return (!empty($area_data) ? $this->getTitleByTypeAndSport($area_data['type_id'], $area_data['sport_id'],
        $type_title) : '') . ($addAreaTitle ? ' - ' . $area_data['title'] : '');
  }
  
  //названия площадок по type_id
  function getAreasTitlesByType($type_id, &$areas_data, $sport_id = false)
  {
    $q = 'select a.*, at.title as type_title, asp.title as sport_title from ' . Query::tableName('areas') . ' a
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id 
			left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id 
      where a.active = "1" and a.type_id = ' . $type_id . ($sport_id !== false ? ' and a.sport_id=' . $sport_id : '')
      . $this->sqlCheckArchivedArea('a') . ' order by a.sort';
    
    $areas_data = Query::sqlQuery($q);
    if (!empty($areas_data)) {
      //получаем строки прощадок
      return true;
    } else {
      $areas_data = null;
      
      return false;
    }
  }
  
  public function getTypesAliasByArray()
  {
    $alias = [];
    $this->getAreasTypesData($types);
    foreach ($types as $type) {
      $alias[] = $type['alias'];
    }
    
    return $alias;
  }
  
  public function getSportsByType($onlyActive = true, $allGroupTypes = false)
  {
    static $sport_by_type = [];
    if (empty($sport_by_type[(int)$allGroupTypes][(int)$onlyActive])) {
      $useGroup   = Query::getDB()->checkField('group', 'areas_types');
      $areas_data = $this->getAreasFullData(['key' => 'area_id', 'as' => 'dto', 'dtoClass' => AreaDto::class]);
      $query      = 'select
       typ.`type_id`,
       typ.`type_id` as type,
       typ.`title` as type_title,
       typ.`alias` as type_alias,
       typ.`active` as type_active,
       typ.`sort` as type_sort,
       typ.`comments` as type_comments,
       CONCAT(typ.`alias`, "_", typ.`type_id`) as type_alias_type_id, 
       (select count(distinct tca_a.`type_id`) from ' . Query::tableName('areas') . ' tca_a
        join ' . Query::tableName('areas_types') . ' tca_at on tca_a.`type_id` = tca_at.`type_id` 
        and tca_at.`alias`=typ.`alias`' . ($useGroup ? ' and tca_at.`group` = typ.`group`' : '') . ') as type_count_alias,
       (select count(distinct tc_a.`type_id`) from ' . Query::tableName('areas') . 'tc_a 
        join ' . Query::tableName('areas_types') . ' tc_at on tc_a.`type_id` = tc_at.`type_id`'
        . ($useGroup ? ' and tc_at.`group` = typ.`group`' : '') . ') as type_count,
       spr.`sport_id`,
       spr.`sport_id` as sport,
       spr.`title` as sport_title,
       spr.`alias` as sport_alias,
       spr.`sort` as sport_sort,
       spr.`comment` as sport_comment,
       (select count(distinct `sport_id`) from ' . Query::tableName('areas') . ' where `type_id` = aba.`type_id`) as sport_count,
       JSON_ARRAYAGG(
          JSON_OBJECT(
            \'area_id\', aba.area_id
          )
      ) AS areas
      from ' . Query::tableName('areas') . ' aba
       left join ' . Query::tableName('areas_types') . ' typ on typ.`type_id` = aba.`type_id`
       left join ' . Query::tableName('areas_sports') . ' as spr on spr.`sport_id` = aba.`sport_id`'
        . ' where aba.`area_id` is not null'
        . (!$allGroupTypes ? $this->sqlCheckGroupType('typ') : '')
        . ($onlyActive ? ' and aba.`active` = "1"' : '')
        . $this->sqlCheckArchivedArea('aba')
        . ' group by typ.`type_id`, spr.`sport_id`, aba.`period` order by typ.`sort`, spr.`sort`';
      foreach (Query::sqlQuery($query, [], true, ['style' => PDO::FETCH_CLASS]) as $row) {
        //Делаем костыль для замены названий типа и спорта, по выбраному языку
        $row->sport_title = lang('sport_title_' . $row->sport_alias, 'sports_titles', [], $row->sport_title);
        $row->type_title = lang('type_title_' . $row->type_alias, 'types_titles', [], $row->type_title);
        $areas = json_decode($row->areas);
        unset($row->areas);
        $key             = $row->type . '_' . $row->sport;
        $row->title_full = $row->type_title . ' - ' . $row->sport_title;
        if ($row->type_count == 1) {
          $row->title = $row->sport_title;
        } else {
          $row->title = $row->type_title . ($row->sport_count > 1 ? ' - ' . $row->sport_title : '');
        }
        $row->title_site_url = '';
        if (Engines::checkShowTitleSport($row->type_id, $row->sport_id) && Engines::checkShowTitleSportToType($row->type_id,
            $row->sport_id)) {
          $row->title_site_url = $row->title;
        } elseif (!Engines::checkShowTitleSport($row->type_id, $row->sport_id) && Engines::checkShowTitleSportToType($row->type_id,
            $row->sport_id)) {
          $row->title_site_url = $row->type_title;
        } elseif (Engines::checkShowTitleSport($row->type_id, $row->sport_id) && !Engines::checkShowTitleSportToType($row->type_id,
            $row->sport_id)) {
          $row->title_site_url = $row->sport_title;
        }
        if (!isset($sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key])) {
          $sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key] = $row;
        }
        if (empty($sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->areas)) {
          $sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->areas = [];
        }
        foreach ($areas as $area) {
          if (empty($sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->periods) || !in_array($areas_data[$area->area_id]->period, $sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->periods)) {
            $sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->periods[] = $areas_data[$area->area_id]->period;
          }
          $sport_by_type[(int)$allGroupTypes][(int)$onlyActive][$key]->areas[$area->area_id] = $areas_data[$area->area_id];
        }
      }
    }
    
    return $sport_by_type[(int)$allGroupTypes][(int)$onlyActive] ?? [];
  }
  
  function getAreaPriceOrderBySeasonTimeWeekday($area_id, &$result, &$error_code)
  {
    $result = [];
    //max и min рабочее время
    $q    = 'select min(start) as start, max(finish) as finish from ' . Query::tableName('areas_timetables') . ' 
                    where area_id = ' . $area_id;
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      foreach ($temp as $tmp) {
        $result['min_start']  = substr($tmp['start'], 0, 5);
        $result['max_finish'] = substr($tmp['finish'], 0, 5);
      }
      
      //пробиваем рабочее время по дням недели
      $q = 'select weekday, substring(start, 1, 5) as start, substring(finish, 1, 5) as finish from ' . Query::tableName('areas_timetables') . ' 
          where area_id=' . $area_id . '
					group by weekday
					order by weekday';
      
      $temp = Query::sqlQuery($q);
      foreach ($temp as $tmp) {
        $result['time_by_weekday'][$tmp['weekday']] = [$tmp['start'], $tmp['finish']];
      }
      
      
      //пробиваем цены
      $q = 'SELECT pp . title, pp . period_id, p . weekday, substring(p . start, 1,
    5) as start, p . price 
                FROM ' . Query::tableName('areas_prices_periods') . ' pp
                LEFT JOIN ' . Query::tableName('areas_prices') . ' p ON p . period_id = pp . period_id
                left join ' . Query::tableName('areas') . ' a on a . area_id = p . area_id where a . area_id = ' . $area_id . ' 
                order by p . weekday, p . start';
      
      foreach ($temp as $tmp) {
        $result['prices'][$tmp['period_id']][$tmp['start']][$tmp['weekday']] = $tmp['price'];
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
  }
  
  public function getAreasByPageAndTypeAndSport($type_id, $sport_id, $page)
  {
    $q    = 'select * from ' . Query::tableName(
      'areas'
      ) . ' where active = "1" and type_id=? and sport_id=? and page=?' . $this->sqlCheckArchivedArea() . ' order by sort';
    $temp = Query::sqlQuery($q, [$type_id, $sport_id, $page]);
    if (!empty($temp)) {
      $result = [];
      foreach ($temp as $row) {
        $result[$row['area_id']] = $row;
      }
      
      return $result;
    }
    
    return false;
  }
  
  public function getAreasPages($type_id = null, $sport_id = null): array
  {
    $where = $params = $result = [];
    if ($type_id) {
      $params[] = (int)$type_id;
      $where[]  = 'type_id=?';
    }
    if ($sport_id) {
      $params[] = (int)$sport_id;
      $where[]  = 'sport_id=?';
    }
    $q = 'select * from ' . Query::tableName('areas') .
      ' where active = "1"' . $this->sqlCheckArchivedArea() . ($where ? ' and (' . implode(' and ', $where) . ')' : '') .
      ' order by sort';
    if ($temp = Query::sqlQuery($q, $params)) {
      $i = 0;
      foreach ($temp as $row) {
        $row['start_limit']                                               = $i;
        $result[$row['type_id'] . '_' . $row['sport_id']][$row['page']][] = $row;
        $i++;
      }
    }
    return $result;
  }

  /**
   * Возвращает активные страницы площадок и выбирает первую доступную,
   * если запрошенная страница отсутствует.
   *
   * @return array{page: int, limit: ?array{0: int, 1: int}, pages: array}
   */
  public function resolveAreasPage(int $typeId, int $sportId, int $page): array
  {
    $pages = $this->getAreasPages($typeId, $sportId)[$typeId . '_' . $sportId] ?? [];

    return AreasPageResolver::resolve($pages, $page);
  }
  
  public function getAreaPricesByTimes($area_id, $times, $period_id = null, $weekday = null)
  {
    $query  = 'select start, price from ' . Query::tableName('areas_prices') . ' 
    where period_id=:period_id and area_id=:area_id and weekday=:weekday and start in (' . ('"' . implode(
          ':00","',
          $times
        ) . ':00"') . ')
    order by start';
    $prices = [];
    
    foreach (
      Query::sqlQuery(
        $query,
        ['area_id' => $area_id, 'period_id' => $period_id, 'weekday' => $weekday]
      ) as $value
    ) {
      $prices[substr($value['start'], 0, -3)] = $value['price'];
    }
    
    return Query::sqlQuery($query, ['area_id' => $area_id, 'period_id' => $period_id, 'weekday' => $weekday]);
  }
  
  public function setWebIoStatus($area_id, $webIo = [])
  {
    if (!empty($webIo)) {
      $set = [];
      foreach ($webIo as $key => $value) {
        $set[] = $key . '=\'' . $value . '\'';
      }
      
      Query::sqlQuery('update ' . Query::tableName('areas') . ' set ' . implode(', ', $set) . ' where area_id=' . $area_id, [], false);
    }
  }
  
  public function selectSportsByType($type_id)
  {
    $query = 'select asp.sport_id, asp.title, asp.alias from ' . Query::tableName('areas') . ' as a
      left join ' . Query::tableName('areas_sports') . ' as asp on a.sport_id=asp.sport_id  
      where a.active = "1" and a.type_id=' . $type_id . $this->sqlCheckArchivedArea('a') . ' group by asp.sport_id order by asp.sort';
    
    return Query::sqlQuery($query, [], true, ['style' => PDO::FETCH_CLASS]);
  }
  
  public function selectPageByTypeAndSports($type_id, $sport_id)
  {
    $query = 'select asp.sport_id, asp.title, asp.alias, a.title as area_title, a.sort from ' . Query::tableName('areas') . ' as a
      left join ' . Query::tableName('areas_sports') . ' as asp on a.sport_id=asp.sport_id  
      where a.active = "1" and a.type_id=' . $type_id . ' and a.sport_id=' . $sport_id
      . $this->sqlCheckArchivedArea('a') . ' order by a.sort ';
    
    return Query::sqlQuery($query, [], true, ['style' => PDO::FETCH_CLASS]);
  }
  
  /**
   *  Получение активных типов площадок
   *
   * @param string $fieldName           - поле для сортировки
   * @param bool   $checkActivityByType - проверять активность по типу площадки, если нет, то только активность по площадкам этого типа
   * @return array<AreaTypeDto>
   */
  public function selectActiveType(string $fieldName = 'type_id', bool $checkActivityByType = false): array
  {
    static $types;
    $typesFields = ['type_id', 'alias', 'title', 'current_alias'];
    $fieldName   = in_array($fieldName, $typesFields, true) ? $fieldName : 'type_id';
    
    if (empty($types)) {
      $query = 'SELECT at.type_id, at.title, at.alias, CONCAT(at.alias, "_", at.type_id) AS type_alias_type_id,
      (SELECT count(type_id) FROM ' . Query::tableName('areas_types') . ' WHERE alias=at.alias) AS type_count_alias,
      at.active, at.sort
      FROM ' . Query::tableName('areas') . ' AS a
      INNER JOIN ' . Query::tableName('areas_types') . ' AS at ON a.type_id=at.type_id
      WHERE a.active = 1' . ($checkActivityByType ? ' AND at.active = 1' : '')
        . $this->sqlCheckArchivedArea('a')
        . $this->sqlCheckGroupType('at')
        . ' GROUP BY at.type_id ORDER BY at.sort';
      foreach (Query::sqlQuery($query) as $type) {
        $type['current_alias'] = $type['type_count_alias'] > 1 ? $type['type_alias_type_id'] : $type['alias'];
        $type                  = AreaTypeDto::fromArray($type);
        foreach ($typesFields as $typeField) {
          $types[$typeField][$type->$typeField] = $type;
        }
      }
    }
    
    return !empty($types[$fieldName])?$types[$fieldName]:[];
  }
  
  public function getWorkTimeByTypeSport($type, $sport, $byTime = false, $second = true)
  {
    $result = [];
    
    $workDataTime = $this->getWorkDataTimeByTypeSport($type, $sport);
    for ($i = 0; $i < 7; $i++) {
      foreach (TimeHelper::generateArrayTimeInIncrements($workDataTime->min, $workDataTime->max, $workDataTime->period, $second) as $time) {
        $title = TimeHelper::generateTitleByTimeAndPeriod($time, $workDataTime->period, $second);
        if ($byTime) {
          $result[$time][$i] = $title;
        } else {
          $result[$i][$time] = $title;
        }
      }
    }
    
    return $result;
  }
  
  /**
   * @param $type
   * @param $sport
   *
   * @return mixed
   */
  public function getWorkDataTimeByTypeSport($type, $sport)
  {
    $query = 'select min(ata.start) as min, max(ata.finish) as max, a.period from ' . Query::tableName('areas') . ' a
      left join ' . Query::tableName('areas_timetables') . ' ata on ata.area_id = a.area_id 
      where a.type_id = ' . (int)$type . ' and a.sport_id = ' . (int)$sport . $this->sqlCheckArchivedArea('a');
    
    return Query::sqlQuery($query, [], true, ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]);
  }
  
  public function getAreasWebIoTypeStateOn()
  {
    return Query::sqlQuery(
      'select max(light_on) as light, max(heating_on) as heating, max(net_on) as net from ' . Query::tableName('areas')
      . ' where 1=1' . $this->sqlCheckArchivedArea(),
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]
    );
  }
  
  public function getMinSportByType($typeId)
  {
    return Query::sqlQuery(
      'select min(sport_id) as id from ' . Query::tableName('areas') . ' where type_id=? and active=1'
      . $this->sqlCheckArchivedArea(),
      [(int)$typeId],
      true,
      ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]
    )->id;
  }
  
  public function getPricesForPlayers(
    $type_id = false,
    $sport_id = false,
    $area_id = false,
    $type_game = false,
    $main_player = false,
    $other_player = false,
    $count_player = false
  ) {
    $result      = [];
    $placeHolder = [];
    $where       = '';
    $query       = 'select * from ' . Query::tableName('areas_prices_for_players');
    if (!empty($area_id)) {
      $type_id  = false;
      $sport_id = false;
      Query::setWhereWithPlaceholder('area_id', $area_id, $where, $placeHolder);
      if (count(Query::sqlQuery($query . $where, $placeHolder)) == 0) {
        $placeHolder = [];
        $where       = '';
        $this->getAreaData($area_id, $area_data);
        $type_id  = $area_data['type_id'];
        $sport_id = $area_data['sport_id'];
      }
    }
    
    Query::setWhereWithPlaceholder('type_id', $type_id, $where, $placeHolder);
    Query::setWhereWithPlaceholder('sport_id', $sport_id, $where, $placeHolder);
    Query::setWhereWithPlaceholder('type_game', $type_game, $where, $placeHolder);
    Query::setWhereWithPlaceholder('main_player', $main_player, $where, $placeHolder);
    Query::setWhereWithPlaceholder('other_player', $other_player, $where, $placeHolder);
    Query::setWhereWithPlaceholder('count_player', $count_player, $where, $placeHolder);
    foreach (Query::sqlQuery($query . $where, $placeHolder) as $row) {
      $result[$row['type_game']][$row['main_player']][$row['other_player']][$row['count_player']] = $row;
    }
    
    return $result;
  }
  
  public function savePricesForPlayers($prices)
  {
    $fields = '(`type_id`,`sport_id`,`area_id`,`type_game`,`main_player`,`other_player`,`count_player`,`price`)';
    $values = [];
    foreach ($prices as $row) {
      $values[] = '\'' . implode(
          '\',\'',
          [
            $row['type_id'],
            $row['sport_id'],
            $row['area_id'],
            $row['type_game'],
            $row['main_player'],
            $row['other_player'],
            $row['count_player'],
            $row['price']
          ]
        ) . '\'';
    }
    if (count($values) > 0) {
      $values = '(' . implode('),(', $values) . ')';
      Query::sqlQuery('replace into ' . Query::tableName('areas_prices_for_players') . ' values ' . $values, [], false);
    }
  }
  
  public function getFirstActiveType($property = 'type_id')
  {
    $result = Query::sqlQuery(
      'select ' . implode(', ', !is_array($property) ? (array)$property : $property) . ' from ' . Query::tableName('areas_types')
      . ' where type_id = (select min(type_id) from ' . Query::tableName('areas') . ' where active=1'
      . $this->sqlCheckArchivedArea() . ')',
      [],
      true,
      ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]
    );
    
    return is_array($property) ? $result : $result->{$property};
  }
  
  public function getAreasTitles(): array
  {
    $areas = [];
    if ($this->getAllAreasData($areas_data)) {
      $s_b_t = $this->getSportsByType();
      foreach ($areas_data as $area_data) {
        $areas[$area_data['area_id']] = $s_b_t[$area_data['type_id'] . '_' . $area_data['sport_id']]->title_site_url . ' - ' . $area_data['title'];
      }
    }
    
    return $areas;
  }
  
  /**
   * @param int  $type_id
   * @param bool $current - с type_id если алиасов больше 1
   * @return string
   */
  public function getAliasType(int $type_id, bool $current = false): string
  {
    
    return $this->selectActiveType()[$type_id]->{($current ? 'current_' : '') . 'alias'};
  }
  
}
