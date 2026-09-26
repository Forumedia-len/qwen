<?php

namespace AC\core\modules\webIo\models;

use AC\core\system\db\Query;

use PDO;

class WebIoOutputsModel
{
  public $webio_id;
  public $port;
  public $area_id;
  public $webio_type_id;
  public $pre_start_time;

  protected static $table = 'webio_outputs';

  public static function getAll($render = true)
  {
    $result = Query::sqlQuery('select * from ' . Query::tableName(self::$table), array(), true, array('style' => PDO::FETCH_CLASS));

    return $render ? self::renderGetResult($result) : $result;
  }

  public static function getByWebIoId($webIoId, $render = true)
  {
    $result = Query::sqlQuery(
      'select * from ' . Query::tableName(self::$table) . ' where webio_id=:webio_id',
      array(':webio_id' => $webIoId),
      true,
      array('style' => PDO::FETCH_CLASS)
    );

    return $render && $webIoId && $result ? self::renderGetResult($result)[$webIoId] : $result;
  }

  private static function renderGetResult($result)
  {
    $out = array();
    foreach ($result as $item) {
      $out[$item->webio_id][$item->port]['area_id'][]      = $item->area_id;
      $out[$item->webio_id][$item->port]['webio_type_id']  = $item->webio_type_id;
      $out[$item->webio_id][$item->port]['pre_start_time'] = $item->pre_start_time;
    }

    return $out;
  }

  public static function insert($webIo_id, $outputs)
  {
    if (is_array($outputs) && !empty($outputs)) {
      $qry = $s = '';
      foreach ($outputs as $params) {
        $qry .= $s . '("' . $webIo_id . '", "' . $params['port'] . '", "' . $params['area_id'] . '", "' . $params['webio_type_id'] . '", ' . (isset($params['pre_start_time'])
            ? '"' . $params['pre_start_time'] . '"' : 'null') . ')';
        $s   = ', ';
      }

      $query = 'insert into ' . Query::tableName(self::$table) . ' (`webio_id`, `port`, `area_id`, `webio_type_id`, `pre_start_time`) 
    value ' . $qry;
      Query::sqlQuery($query, array(), false);

      return true;
    }

    return false;
  }

  public static function deleteByWebIoId($webIoId)
  {
    return Query::sqlQuery('delete from ' . Query::tableName(self::$table) . ' where webio_id=:webio_id', array(':webio_id' => $webIoId), false);
  }

  public static function save($webIo_id, $outputs)
  {
    if (self::deleteByWebIoId($webIo_id)) {
      return self::insert($webIo_id, $outputs);
    }

    return false;
  }
}