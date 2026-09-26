<?php

namespace AC\app\cron;

use AC\core\engines\Engines;
use AC\core\system\db\Query;
use AC\core\system\deploy\DeployFile;
use AC\core\system\helpers\TimeHelper;
use Service;

class Cron
{
  /**
   * @var Engines
   */
  public $engine;

  public function start()
  {
    global $argc, $argv;
    $action       = $argc > 1 ? $argv[1] : Service::request()->_get('action');
    $this->engine = new Engines();
    switch ($action) {
      case 'removeUnconfirmed':
        $this->removeUnconfirmed();
        break;
      case 'deployClient':
        $this->deployClient();
        break;
      default:
        break;
    }
  }

  /**
   * @todo  нужно будет доработать если будет использоваться смещение по времени
   *        сейчас используются промежутки 00 и 30 минут
   */
  protected function removeUnconfirmed()
  {
    if(defined('CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN') && CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN) {
      $confTime      = explode('_', CONFIRMATION_OF_RESERVATIONS_VIA_TOUCH_FOR_OPEN);
      $time = date('H:i');
      $date = date('Y-m-d');
      $minutes = (int) TimeHelper::returnPartTimeFromStringMysqlTime($time);

      $time = date('H:i:s', strtotime(date('H:00', strtotime($time)) . ' + '. ($minutes <= 30 ? ($confTime[0] > 0 ? 30 : 0) : ($confTime[0] > 0 ? 60 : 30)) . ' minutes'));
      $query = 'select r.*, a.period  from ' . Query::tableName('reservations') . ' as r '
        . 'left join ' . Query::tableName('areas') . ' as a on a.area_id = r.area_id and a.type_id=:type_id and a.sport_id=:sport_id'
        . ' where r.status=\'0\' and r.main_reservation_id is null and r.start <= "' . $date . ' ' . $time . '"';

      foreach (Query::sqlQuery($query, [':type_id' =>2, ':sport_id' =>1]) as $item) {
        $this->engine->removeReservationById($item['reservation_id']);
      }
    }
  }
  protected function deployClient(): void
  {
    $this->engine->nds->getNdss($nds_data);

    $out  = "E-Mail;Vorname;Familienname;Telefon;Mobil;Anschrift;Geburtstag;Ort;Fax;Datum der letzten Buchung;Wert der letzten Buchung (bezahlter Betrag);PLZ;Datum der nächsten zukünftigen Buchung;Kontaktkategorie\n";
    $date = date('Y-m-d');
    if ($this->engine->clients->getClientsData(null, null, $clients)) {
      foreach ($clients as $item) {
        if ($this->engine->clients->checkEmail($item['email'])) {
          $reservation = $this->engine->getLastAndNextReservationByClientBeforeDate($item['client_id'], $date);
          list($last, $next) = array($reservation['last'], $reservation['next']);
          $a   = array(
            $item['email'],
            $item['name'],
            $item['surname'],
            $item['phone'],
            $item['phone_mobile'],
            $item['address'],
//        (!empty($item['birthday']) && validateDate($item['birthday'], 'Y-m-d')) ? convertMysql2Date($item['birthday']) : '',
            '',// просили сделать это поле пустым birthday
            $item['city'],
            $item['fax'],
            !empty($last) ? date('d-m-Y', strtotime($last['start'])) : '',
            !empty($last) ? number_format($last['sum_price'], 2, '.', ' ') : '',
            $item['post_code'],
            !empty($next) ? date('d-m-Y', strtotime($next['start'])) : '',
            'McArena ' . ucfirst(EXPORT_COURT)
          );
          $out .= join(';', $a) . "\n";
        }
      }
    }
    $content   = "\xEF\xBB\xBF" . $out;
    $file_name = 'export-' . mb_strtolower((defined('EXPORT_COURT_NAME_FILE') ? EXPORT_COURT_NAME_FILE : EXPORT_COURT)) . '_' . date('Y-m-d_H-i-s') . '.csv';
    $dir       = pathAs(paths()->getTmpFilesDir());
    file_put_contents($dir . $file_name, $content);
    $deploy = new DeployFile($dir, $file_name);
    $deploy->deploy();
    unlink($dir . $file_name);
  }
}

$a = new cron();
$a->start();