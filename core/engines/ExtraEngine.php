<?php

namespace AC\core\engines;

use AC\app\services\DataService;
use AC\core\system\db\Query;

use AC\core\system\engine\BaseEngine;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DataHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\object\BaseObject;
use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\config\models\ConfigExtraModel;
use PDO;

/**
 * Class ExtraEngine - Проводимые акции
 */
class ExtraEngine extends BaseEngine
{

  public function modelName($modelName = false)
  {
    $modelName = useClass('core\modules\config\models\ConfigExtraModel');

    return parent::modelName($modelName);
  }

  /**
   * {@inheritDoc}
   */
  public function tableName()
  {
    return 'config_extra';
  }

  /**
   * Получить все актуальные записи, сформированные по типу и спорту, и rate объединен
   *
   * @param      $_extra
   * @param bool $byTypeSport
   *
   * @return bool
   */
  public function getExtra(&$_extra, $byTypeSport = false)
  {
    $extra = $this->all();
    $areas = DataService::sportsByType();
    foreach ($areas as $key => $data) {
      [$type, $sport] = explode('_', $key);
      $extra_data        = $this->getExtraByTypeAndSport($type, $sport, $extra);
      $extra_data->title = $data->title;
      if ($byTypeSport) {
        $_extra[$type . '_' . $sport] = $extra_data;
      } else {
        $_extra[] = $extra_data;
      }
    }
    if ($_extra) {
      return true;
    }

    return false;
  }

  public function getAllExtra(array $params = []): array
  {
    static $extra;
    $result = [];
    if (empty($extra)) {
      $query = 'SELECT ce.*,
       COALESCE((SELECT JSON_ARRAYAGG( JSON_OBJECT(\'extra_id\', extra_id, \'weekday\', weekday, \'start\', start, \'price\', price))
        FROM ' . Query::tableName('config_extra_week') . ' WHERE extra_id = ce.extra_id), JSON_ARRAY()) as week_times
        FROM ' . Query::tableName('config_extra') . ' as ce ';
      $extra = Query::sqlQuery($query) ?? [];
    }
    foreach ($extra as $row) {
      $result[$row['area_type_id'] . '_' . $row['area_sport_id']][$row['club_state']] = DataHelper::getAs($row, $params['as'] ?? 'array', $params);
    }

    return $result;
  }

  /**
   *
   *  Получить наценки по id площадки
   *
   * @param $area_id
   *
   * @return BaseObject
   */
  public function getExtraByAreaId($area_id)
  {
    $query = 'SELECT ce.* FROM ' . $this->query->getTableName() . ' as ce 
        LEFT JOIN ' . $this->query->getTableName('areas') . ' as a ON a.area_id = :area_id 
        WHERE a.type_id = ce.area_type_id AND a.sport_id = ce.area_sport_id';
    $extra = Query::sqlQuery(
      $query,
      ['area_id' => $area_id],
      true,
      [
        'style'    => PDO::FETCH_CLASS,
        'argument' => module('config')->getFullModelName(StringHelper::underscoreToCamelCase($this->tableName()) . 'Model')
      ]
    );

    return $this->getExtraByTypeAndSport($extra[0]->area_type_id, $extra[0]->area_sport_id, $extra);
  }

  public function getExtraByClubState($club_state)
  {
    static $extras;
    if(empty($extras[$club_state])) {
      $query = 'select * from ' . $this->query->getTableName() . ' as ce where ce.club_state = :club_state';

      $_extra = Query::sqlQuery(
        $query,
        ['club_state' => $club_state],
        true,
        ['style' => PDO::FETCH_CLASS, 'argument' => 'stdClass']
      );
      $extra  = [];
      foreach ($_extra as $value) {
        $extra[$value->area_type_id . '_' . $value->area_sport_id] = $value;
      }
      $extras[$club_state] = $extra;
    } else {
      $extra = $extras[$club_state];
    }

    return $extra;
  }

  /**
   *
   * Получить наценки по типу площадку и типу спорта
   *
   * @param      $type
   * @param      $sport
   * @param null $_extra
   *
   * @return BaseObject|object
   */
  public function getExtraByTypeAndSport($type, $sport, $_extra = null)
  {

    if ($_extra === null) {
      $query  = 'select ce.*  from ' . $this->query->getTableName() . ' as ce 
                where ce.area_type_id=' . $type . ' and ce.area_sport_id=' . $sport;
      $_extra = Query::sqlQuery(
        $query,
        [],
        true,
        ['style' => PDO::FETCH_CLASS, 'argument' => module('config')->getFullModelName('ConfigExtraModel')]
      );
    }
    $_rate = [];
    /** @var $state ConfigClubStateModel */
    $club_rate = [];
    $new       = false;
    foreach (ConfigExtraModel::getClubStates() as $state) {
      $extra_id   = null;
      $rate       = 0;
      $extra_data = null;
      foreach ($_extra as $extra) {
        if ($extra->area_type_id == $type && $extra->area_sport_id == $sport && $extra->club_state == $state->id) {
          $extra_id   = $extra->extra_id;
          $rate       = $extra->rate;
          $extra_data = $extra;
        }
      }
      if ($extra_id === null) { // если не существует, создаем модель и наполняем ее данными и сохраняем в таблице
        $model                            = new ConfigExtraModel();
        $model->area_type_id              = $type;
        $model->area_sport_id             = $sport;
        $model->rate                      = $rate;
        $model->club_state                = $state->{$state->getPrimaryKey()};
        $model->use_time                  = 0;
        $model->{$model->getPrimaryKey()} = $model->save();
        $new                              = true;
        $extra_data                       = $model;
      }
      $club_rate[$state->mark] = $rate;
      $_rate[$state->id]       = (object)[
        'club_state_id' => $state->id,
        'title'         => $state->title,
        'mark'          => $state->mark,
        'extra_id'      => $extra_id ?? $model->extra_id,
        'rate'          => $rate,
        'extra'         => $extra_data,
      ];
    }

    return (object)array_merge(
      $club_rate,
      [
        'title' => (new AreasModel)->getTitleByTypeAndSport($type, $sport),
        'type'  => $type,
        'sport' => $sport,
        'rate'  => $_rate,
        'new'   => $new,
      ]
    );
  }

  function getExtraWeek($extra_id)
  {
    $result = [];
    $qyery  = Query::sqlQuery('select * from ' . Query::$prefix . 'config_extra_week where extra_id=' . $extra_id);
    if ($qyery) {
      foreach ($qyery as $row) {
        $result[$row['weekday']][$row['start']] = $row['price'];
      }
    }

    return $result;
  }

  public function updateRowExtraWeek($extra_id, $weekday, $start, $price)
  {
    $tableName = Query::$prefix . 'config_extra_week';
    $params    = [
      ':extra_id' => $extra_id,
      ':start'    => $start,
      ':weekday'  => $weekday,
    ];
    if (Query::sqlQuery(
      'select * from ' . $tableName . ' where extra_id = :extra_id and weekday = :weekday and start = :start',
      $params
    )) {
      $params[':price'] = $price;
      Query::sqlQuery(
        'update ' . $tableName . ' set price=:price where extra_id=:extra_id and weekday=:weekday and start=:start',
        $params,
        false
      );
    } else {
      $params[':price'] = $price;
      Query::sqlQuery(
        'insert into ' . $tableName . ' (extra_id, weekday, start, price)  values (:extra_id, :weekday, :start, :price)',
        $params,
        false
      );
    }
  }

  function getExtraWeekPrice($extra_id, $weekday, $start)
  {
    $res = Query::sqlQuery(
      'select price from ' . Query::$prefix . 'config_extra_week where extra_id = :extra_id and weekday = :weekday and start = :start',
      $params = [
        ':extra_id' => $extra_id,
        ':start'    => $start,
        ':weekday'  => $weekday,
      ]
    );

    return $res[0]['price'] ?? 0;
  }

  public static function getExtraRate($club_state, $type_id, $sport_id, $mysql_date, $mysql_time): float
  {
    $query     = '
        SELECT 
            IF(
                ce.use_time = 1,
                COALESCE(w.price, 0),
                ce.rate
            ) AS extra
        FROM ' . Query::tableName('config_extra') . ' ce
        LEFT JOIN ' . Query::tableName('config_extra_week') . ' w
            ON w.extra_id = ce.extra_id
            AND w.weekday = ?
            AND w.start = ?
        WHERE ce.club_state = ?
          AND ce.area_type_id = ?
          AND ce.area_sport_id = ?
    ';

    if ($result = Query::sqlQuery(
      $query,
      [
        CalendarHelper::getWeekdayByUnixtime(strtotime($mysql_date . ' ' . $mysql_time)),
        $mysql_time . ':00',
        $club_state,
        $type_id,
        $sport_id,
      ],
      true,
      ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]
    )) {
      return $result->extra;
    }

    return 0;
  }

}
