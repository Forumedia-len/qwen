<?php

namespace AC\core\modules\areas\models;


use AC\core\system\db\Query;

class AreasLightsModel
{
  protected static $table = 'areas_lights';

  /**
   * Кеш расписания по площадке: area_id => [ "type|weekday|H:i:s" => count ].
   *
   * @var array<int, array<string, int>>
   */
  private static $defaultStatesByArea = array();

  /**
   * @param string|int $start_time время слота (H:i:s, H:i или строка для strtotime)
   */
  private static function normalizeStartTimeHms($start_time): string
  {
    if (is_string($start_time) && strlen($start_time) === 8 && $start_time[2] === ':' && $start_time[5] === ':') {
      return $start_time;
    }

    return date('H:i:s', strtotime((string)$start_time));
  }

  public static function changeDefaultState($area_id, $data, $type)
  {
    Query::sqlQuery(
      'delete from ' . Query::tableName(self::$table) . ' where area_id = :area_id AND type=:type',
      array(':area_id' => $area_id, ':type' => $type),
      false
    );
    if (is_array($data) && !empty($data)) {
      $qry = $s = '';
      foreach ($data as $params) {
        $qry .= $s . '("' . $area_id . '", "' . $type . '", "' . $params[0] . '", "' . $params[1] . '")';
        $s   = ', ';
      }
      Query::sqlQuery('insert into ' . Query::tableName('areas_lights') . ' (`area_id`, `type`, `weekday`, `start`) VALUES ' . $qry, array(), false);
    }

    unset(self::$defaultStatesByArea[(int)$area_id]);

    return true;
  }

  public static function getDefaultStateData($area_id, $type)
  {
    $items = array();
    foreach (
      Query::sqlQuery(
        'select * from ' . Query::tableName(self::$table) . ' WHERE area_id = :area_id AND type=:type',
        array(':area_id' => $area_id, ':type' => $type)
      ) as $row
    ) {
      $items[$row['area_id']][$row['weekday']][] = date('H:i', strtotime($row['start']));
    }

    return $items;
  }


  public static function getAreasDefaultStateData()
  {
    $items = array();
    foreach (
      Query::sqlQuery('select * from ' . Query::tableName(self::$table)) as $row
    ) {
      $items[$row['type']][$row['area_id']][$row['weekday']][] = date('H:i', strtotime($row['start']));
    }

    return $items;
  }

  public static function checkAreasDefaultState($area_id, $weekday, $start_time, $type)
  {
    $aid = (int)$area_id;
    if (!isset(self::$defaultStatesByArea[$aid])) {
      self::prefetchDefaultStatesByArea($aid);
    }
    $cacheKey = (int)$type . '|' . (int)$weekday . '|' . self::normalizeStartTimeHms($start_time);
    if (!isset(self::$defaultStatesByArea[$aid][$cacheKey])) {
      return 0;
    }

    return (int)self::$defaultStatesByArea[$aid][$cacheKey];
  }

  /**
   * Одна выборка расписания по площадке вместо множества checkAreasDefaultState (N×типы WebIO×слоты).
   * Пишет только плоский кеш {@see self::$defaultStatesByArea}; вложенную матрицу не строит (нигде не использовалась).
   */
  public static function prefetchDefaultStatesByArea(int $area_id): void
  {
    if (isset(self::$defaultStatesByArea[$area_id])) {
      return;
    }

    $flat = array();
    foreach (
      Query::sqlQuery(
        'SELECT `type`, `weekday`, `start` FROM ' . Query::tableName(self::$table) . ' WHERE area_id = :area_id',
        array(':area_id' => $area_id)
      ) as $row
    ) {
      $type     = (int)$row['type'];
      $wd       = (int)$row['weekday'];
      $startKey = date('H:i:s', strtotime($row['start']));
      $ck       = $type . '|' . $wd . '|' . $startKey;
      if (!isset($flat[$ck])) {
        $flat[$ck] = 0;
      }
      $flat[$ck]++;
    }

    self::$defaultStatesByArea[$area_id] = $flat;
  }

}