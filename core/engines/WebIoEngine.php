<?php

namespace AC\core\engines;

use AC\core\system\db\Query;

use AC\core\system\helpers\JsonHelper;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoOutputsModel;
use AC\core\system\ftp\FTPClient;
use PDO;

useClass('engines\reservation.light');

class WebIoEngine extends \reservation_light
{
  protected $arr_type
    = [
      1 => "licht",   // Освещение
      2 => "heizen",  // Отопление
      3 => "netz",      // Сеть
    ];

  public function getAllState()
  {
    $times = [];
    $q     = 'select al.* from ' . Query::tableName('areas_lights') . ' al 
      left join ' . Query::tableName('areas') . ' a on a.area_id = al.area_id
      where a.light_on = \'1\' or a.heating_on = \'1\' or a.net_on = \'1\'
      order by al.type, al.start';
    foreach (Query::sqlQuery($q) as $val) {
      $times[$val['area_id'] . '_' . $val['weekday']][$val['type']][] = $val['start'];
    }

    return $times;
  }

  /**
   * @param            $id
   * @param WebIoModel $webIo
   *
   * @return array|bool
   */
  public function getWebIoById($id, &$webIo)
  {
    $webIo = Query::sqlQuery(
      'select * from ' . Query::tableName($this->table) . ' where id=:id',
      ['id' => $id],
      true,
      ['style' => PDO::FETCH_CLASS, 'onlyOne' => true]
    );

    $webIo->pre_start_time = JsonHelper::decode($webIo->pre_start_time);

    return $webIo;
  }

  public function createArray(&$engine, $area_id, $type, &$arr_files)
  {
    $out = $this->runtimeLightDirection($engine, [$area_id], $type, 1);
    if (empty($out)) {
      $out = 'BEGIN:VCALENDAR' . "\n";
      $out .= 'PRODID:-//Forumedia iCal for Web I/O v.1.0//EN' . "\n";
      $out .= 'END:VCALENDAR' . "\n";
    }
    $sv                   = explode('.', HOST_NAME);
    $site                 = $sv[0];
    $str_name             = "{$site}_{$this->arr_type[$type]}_{$area_id}.ical";
    $arr_files[$str_name] = $out;
    $arr_files["{$site}_{$this->arr_type[$type]}_{$area_id}_" . date('Y_m_d') . ".ical"] = $out;
  }

  public function sendFTPCurrentIcal(&$engine, $area_id = 'all', $time_insert = null)
  {
    if (!defined('SEND_FTP_ICAL_FILE')
      || !SEND_FTP_ICAL_FILE
      || !defined('FTP_HOST') || !FTP_HOST) {
      return;
    }

    if (!empty($time_insert)) {
      if ($time_insert != date('Y-m-d')) {
        return;
      }
    }
    $arr_files = [];
    $hasArchivedAt = Query::getDB()->checkField('archived_at', 'areas');
    $q             = 'select * from ' . Query::tableName('areas') . ($hasArchivedAt ? ' where archived_at is null' : '');
    if ($area_id != 'all') {
      $q = 'select * from ' . Query::tableName('areas') . ' where area_id="' . $area_id . '"'
        . ($hasArchivedAt ? ' and archived_at is null' : '');
    }
    $areas = Query::sqlQuery($q);

    foreach ($areas as $area) {
      if ($area['light_on'] == "1") {
        $type = 1;
        $this->createArray($engine, $area['area_id'], $type, $arr_files);
      }
      if ($area['heating_on'] == "1") {
        $type = 2;
        $this->createArray($engine, $area['area_id'], $type, $arr_files);
      }
      if ($area['net_on'] == "1") {
        $type = 3;
        $this->createArray($engine, $area['area_id'], $type, $arr_files);
      }
      if (!empty($arr_files)) {
        FTPClient::getInstanceConnect(true)->uploadFilesFromString($arr_files);
      }
    }
  }

  public function getWebIoAreas()
  {
    $webIo = Query::sqlQuery(
      'select area_id, light_on, heating_on, net_on from ' . Query::tableName("areas") . " where `light_on` = '1' or `heating_on` = '1' or `net_on` = '1'",
      [],
      true,
      ['style' => PDO::FETCH_CLASS]
    );

    return $webIo;
  }

  public function getAllWebIo($fromOutputs = false, $onlyActive = false)
  {
    $outputs = $fromOutputs ? WebIoOutputsModel::getAll() : [];
    $out     = [];
    foreach (Query::sqlQuery('select * from ' . Query::tableName($this->table), [], true, ['style' => PDO::FETCH_CLASS]) as $item) {
      if (!$onlyActive || $item->use) {
        if (!empty($item->pre_start_time)) {
          $item->pre_start_time = JsonHelper::decode($item->pre_start_time);
        }
        $out[$item->id] = $item;
        if ($fromOutputs && isset($outputs[$item->id])) {
          $out[$item->id]->output = $outputs[$item->id];
        }
      }
    }

    return $out;
  }

  /**
   * @param WebIoModel $model
   *
   * @return mixed
   */
  public function insert($model)
  {
    return parent::insertBase($model->ip, $model->port, $model->password, $model->use, $model->pre_start_time, $model->name);
  }

  /**
   * @param WebIoModel $model
   *
   * @return bool
   */
  public function update($model)
  {
    return Query::sqlQuery(
      'update ' . Query::tableName($this->table)
      . ' set `name`=:name, `ip`=:ip, `port`=:port, `password`=:password, `use`=:use, `pre_start_time`=:pre_start_time where `id`=:id',
      [
        'name'           => $model->name,
        'ip'             => $model->ip,
        'port'           => $model->port,
        'password'       => $model->password,
        'use'            => isset($model->use) ? $model->use : '0',
        'pre_start_time' => $model->pre_start_time,
        'id'             => $model->id,
      ],
      false
    );
  }

  public function delete($model)
  {
    return Query::sqlQuery('delete from ' . Query::tableName($this->table) . ' where id=:id', [':id' => $model->id], false);
  }

}
