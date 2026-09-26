<?php

namespace AC\core\modules\config\models;

use AC\core\system\db\Query;

class ConfigCountModel
{
  public  $table     = [];
  public    $tableView = [];
  protected $tableForOrientation;

  public function save()
  {
    foreach ($this->table as $item) {
      $q     = 'INSERT INTO ' . Query::tableName('areas_min_max') . '(type_id,sport_id,max_forward_reservation_days_count,min_rejection_days_count,min_rejection_days_count_ticket)
    VALUES (:type_id, :sport_id, :max_forward_reservation_days_count, :min_rejection_days_count, :min_rejection_days_count_ticket)
    ON DUPLICATE KEY UPDATE max_forward_reservation_days_count= :max_forward_reservation_days_count2 ,min_rejection_days_count= :min_rejection_days_count2 ,min_rejection_days_count_ticket= :min_rejection_days_count_ticket2';
      $param = [
        'type_id'                             => $item->type_id,
        'sport_id'                            => $item->sport_id,
        'max_forward_reservation_days_count'  => $item->max_forward_reservation_days_count,
        'min_rejection_days_count'            => $item->min_rejection_days_count,
        'min_rejection_days_count_ticket'     => $item->min_rejection_days_count_ticket,
        'max_forward_reservation_days_count2' => $item->max_forward_reservation_days_count,
        'min_rejection_days_count2'           => $item->min_rejection_days_count,
        'min_rejection_days_count_ticket2'    => $item->min_rejection_days_count_ticket
      ];
      Query::sqlQuery($q, $param, false);
    }

    return true;
  }

  public function request()
  {
    return
      [
        'max_forward_reservation_days_count' => $_REQUEST['max_forward_reservation_days_count'],
        'min_rejection_days_count'           => $_REQUEST['min_rejection_days_count'],
        'min_rejection_days_count_ticket'    => $_REQUEST['min_rejection_days_count_ticket']
      ];
  }

  public function load($data)
  {
    foreach ($data['max_forward_reservation_days_count'] as $type => $type_sport) {
      foreach ($type_sport as $sport => $val) {
        $ob['max_forward_reservation_days_count'] = $val;
        $ob['min_rejection_days_count']           = $data['min_rejection_days_count'][$type][$sport];
        $ob['min_rejection_days_count_ticket']    = $data['min_rejection_days_count_ticket'][$type][$sport];
        $ob['type_id']                            = $type;
        $ob['sport_id']                           = $sport;
        $this->table[]                            = (object)$ob;
      }
    }

    return true;
  }

  public function loadParams()
  {
    $temp                      = Query::sqlQuery('SELECT * FROM ' . Query::tableName('areas_min_max'));
    $this->tableForOrientation = [];
    foreach ($temp as $item) {
      $this->tableView[$item['type_id']][$item['sport_id']] = $item;
    }
  }

}
