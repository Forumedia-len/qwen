<?php
//Входные данные area_id, 
//type 1 - свет
//     2 - тепло
//     3 - сеть


use AC\core\engines\Engines;

define('PATH', '../');
define('MODE', 1);


//инициализация
useFile( 'include/main.php');
useFile( 'include/useClass.php');
useClass('core\\engines\\Engines');
useClass('core\\modules\\webIo\\models\\WebIoLogModel');
useClass('core\system\App');

$engine = $r = new Engines();
useFile( 'config.php');

if (Service::request()->checkGet('area_id') && Service::request()->checkGet('type')) {
  $area_id = explode(',', Service::request()->_get('area_id'));
  $type    = (int)Service::request()->_get('type');
  //создаем файл из полученных данных
  $file_name = 'ac_webio_cal_id_' . implode('', $area_id) . '_type_' . $type . '.ics';


  if ($out = $r->light->runtimeLightDirection($r, $area_id, $type, Service::request()->_get('day', 1))) {
    header("Content-Disposition: attachment; filename=" . $file_name);
    header("Content-Type: application/x-force-download; name=\"" . $file_name . "\"");
    echo $out;
    die;
  } else {
    $r->light->logs->load(
      array(
        'date'     => date('Y-m-d H:i:s'),
        'mode'     => '',
        'output'   => (int)$r->light->lc->getOutput($area_id[0], $type)->port,
        'status'   => 'calendar',
        'result'   => 'error',
        'comment'  => 'calendar not created',
        'webio_id' => (int)$r->light->lc->getOutput($area_id[0], $type)->webio_id
      )
    );
    $r->light->logs->save();

    $out = 'BEGIN:VCALENDAR' . "\n";
    $out .= 'PRODID:-//Forumedia iCal for Web I/O v.1.0//EN' . "\n";
    $out .= 'END:VCALENDAR' . "\n";

    header("Content-Disposition: attachment; filename=" . $file_name);
    header("Content-Type: application/x-force-download; name=\"" . $file_name . "\"");
    echo $out;
    die;
  }
}

if(Service::request()->checkGet('json')) {
  $out = $r->light->runtimeLightDirectionJSON($r);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($out);
  die;
}
?>