<?php

use AC\app\config\WebIOStateConfig;
use AC\core\engines\Engines;
use AC\core\modules\webIo\models\WebIoLogModel;

class lightlogs_admin
{
  /**
   * @var WebIOStateConfig
   */
  protected $lc;

  /**
   * @var Engines
   */
  protected $r;

  function __construct()
  {
    $this->lc = WebIOStateConfig::instance();
    $this->r  = new Engines();
  }

  function start()
  {
    switch (Service::request()->_get('action')) {
      default:
        return $this->getItemList();
    }
  }

  //список новостей
  function getItemList()
  {
    if (Service::request()->checkPost('year') && Service::request()->checkPost('month') && Service::request()->checkPost('days')) {
      $c_date = Service::request()->_post('year') . '-' . Service::request()->_post('month') . '-' . Service::request()->_post('days');
    } else {
      $c_date = date('Y-m-d');
    }

    $this->r->areas->getAllAreasData($areas_data);

    if (!Service::request()->checkPost('area_id')) {
      foreach ($areas_data as $area) {
        if ($area['light_on'] == 1 || $area['heating_on'] == 1 || $area['net_on'] == 1) {
          $area_id = $area['area_id'];
          break;
        }
      }
    } else {
      $area_id = (int)Service::request()->_post('area_id');
    }
    $out = $this->getForm($c_date, $areas_data, $area_id);

    $out .= '<br /><br />';
    if ($items = WebIoLogModel::getLogList($area_id, $c_date)) {
      $type_data        = $this->lc->types;

      if ($this->r->areas->getAllAreasData($areas)) {
        foreach ($areas as $area) {
          if($ports = $this->lc->getAreaPorts($area['area_id'])) {
            foreach ($ports as $port) {
              $area_data[$port->webio_id][$port->port] = array($area['type_title'] . '-' . $area['title'], $type_data[$port->webio_type_id]->title);
            }
          }
        }
      }

      $out .= '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">' . "\n";
      $out .= '<tr>';
      $out .= '<th>'.lang('Date').'</th>';
      $out .= '<th>'.lang('Place').'</th>';
      $out .= '<th>'.lang('Type').'</th>';
      $out .= '<th>'.lang('WebIO', 'webIo').'</th>';
      $out .= '<th>'.lang('Port-Web IO', 'webIo').'</th>';
      $out .= '<th>'.lang('Status', 'webIo').'</th>';
      $out .= '<th>'.lang('Feedback', 'webIo').'</th>';
      $out .= '<th>'.lang('Comment').'</th></tr>' . "\n";

      foreach ($items as $item) {
        $webIo = $this->lc->webIo[$item['webio_id']];
        $out .= '<tr class="' . ($item['result'] == 'error' ? 'red' : ($item['status'] == 'ON' ? 'green' : ($item['status'] == 'OFF' ? 'blue' : ''))) . '">';
        $out .= '<td class="dark">' . date('H:i:s', strtotime($item['date'])) . '</td>';
        $out .= '<td class="light">' . $area_data[$item['webio_id']][$item['output']][0] . '</td>';
        $out .= '<td class="dark">' . $area_data[$item['webio_id']][$item['output']][1] . '</td>';
        $out .= '<td class="light">' . $webIo->name . '</td>';
        $out .= '<td class="light">' . $item['output'] . '</td>';
        $out .= '<td class="dark">' . $item['status'] . '</td>';
        $out .= '<td class="light">' . $item['result'] . '</td>';
        $out .= '<td class="dark">' . $item['comment'] . '</td>';
        $out .= '</tr>' . "\n";
      }
      $out .= '</table>' . "\n";
    }

    $output[] = $out;

    return $output;
  }

  function getForm($c_date, $areas_data, $area_id)
  {
    //годы
    $select_years = '<select name="year">' . "\n";
    for ($i = date('Y') - 1; $i <= date('Y'); $i++) {
      $select_years .= '<option value="' . $i . '"' . ($i == date('Y', strtotime($c_date)) ? ' selected' : '') . '>' . $i . '</option>' . "\n";
    }
    $select_years .= '</select>' . "\n";

    //месяцы
    $select_monthes = '<select name="month">' . "\n";
    for ($i = 1; $i <= 12; $i++) {
      $select_monthes .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date('m', strtotime($c_date)) ? ' selected' : '') . '>' . sprintf(
          "%02d",
          $i
        ) . '</option>' . "\n";
    }
    $select_monthes .= '</select>' . "\n";

    //дни
    $select_days = '<select name="days">' . "\n";
    for ($i = 1; $i <= 31; $i++) {
      $select_days .= '<option value="' . sprintf("%02d", $i) . '"' . ($i == date('d', strtotime($c_date)) ? ' selected' : '') . '>' . sprintf(
          "%02d",
          $i
        ) . '</option>' . "\n";
    }
    $select_days .= '</select">' . "\n";

    //площадки
    $select_areas = array();
    foreach ($areas_data as $area) {
      if ($area['light_on'] == 1 || $area['heating_on'] == 1 || $area['net_on'] == 1) {
        $select_areas[$area['area_id']] = $area['type_title'] . ' - ' . $area['title'];
      }
    }

    $out = '<form action="light_logs.php" method="post">' . "\n";
    $out .= '<table cellspacing="1" cellpadding="3" class="main" border="0" align="center">' . "\n";
    $out .= '<tr><th colspan="2">'.lang('Date selection', 'webIo').'</th></tr>' . "\n";
    $out .= '<tr><td class="dark">'.lang('Playground').':</td><td class="dark">' . "\n";
    $out .= '<select name="area_id" class="input">' . "\n";
    foreach ($select_areas as $key => $value) {
      $out .= '<option value="' . $key . '"' . ($key == $area_id ? ' selected' : '') . '>' . $value . '</option>' . "\n";
    }
    $out .= '</select>' . "\n";
    $out .= '</td></tr>' . "\n";
    //Выбор даты
    $out .= '<tr><td class="light">'.lang('Date').':</td><td class="light"><table border="0"><tr><td>' . $select_years . '</td><td>' . $select_monthes . '</td><td>' . $select_days . "</td></tr></table></td></tr>\n";

    $out .= '<tr><td class="dark" colspan="2" align="center"><input type="submit" value="'.lang('Execute').'" class="button"/></td></tr>' . "\n";
    $out .= '</table>' . "\n";
    $out .= "</form>\n";

    return $out;
  }

}

$a = new lightlogs_admin;
$_page['content'] = $a->start();
$_page['key'] = 'light_logs';

