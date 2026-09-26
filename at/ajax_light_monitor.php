<?


use AC\core\engines\Engines;


//подключаем функцию отправки команды на наш мена девайс
$r = new Engines();

//непосредствеено проверка команд
$status = Service::request()->_get('status', false);


if (Service::request()->checkGet('output') && Service::request()->checkGet('webio_id')) {
  $output  = (int)Service::request()->_get('output');
  $webIoId = (int)Service::request()->_get('webio_id');
  if ($status == 'check') {
    if ($st = $r->light->sendSock($output, $status, $error, $webIoId)) {
      echo $st;
    } else {
      echo 'E002: Web I/O not found';
    }
  } else {
    if ($status == 'ON') {
      if (($st = $r->light->sendSock($output, $status, $error, $webIoId)) !== false) {
        if ($r->light->checkSendSockOutput($st, $output, 'ON')) {
          echo 'ON';
        } else {
          echo 'E021';
        }
      } else {
        echo 'E002: Web I/O not found';
      }
    } else {
      if ($status == 'OFF') {
        if (($st = $r->light->sendSock($output, $status, $error, $webIoId)) !== false) {
          if ($r->light->checkSendSockOutput($st, $output, 'OFF')) {
            echo 'OFF';
          } else {
            echo 'E021';
          }
        } else {
          echo 'E002: Web I/O not found';
        }
      } else {
        echo 'E003: State not set';
      }
    }
  }
} else {
  echo 'E004: Output not found';
}
?>