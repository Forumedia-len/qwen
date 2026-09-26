<?php

use AC\app\config\WebIOStateConfig;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\engines\AreasEngine;
use AC\core\engines\Engines;
use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\webIo\models\WebIoLogModel;
use AC\core\modules\webIo\models\WebIoModel;
use AC\core\modules\webIo\models\WebIoOutputsModel;
use AC\core\modules\webIo\models\WebIoTypeSatesModel;

class reservation_light
{
  protected $table = 'webio';

  public static $type_states;

  /**
   * @var WebIOStateConfig
   */
  public $lc;
  public $today;
  public $time_start;

  /**
   * @var WebIoLogModel
   */
  public $logs;

  /**
   * @throws ReflectionException
   */
  public function __construct()
  {
    $this->setConfig();
  }

  function initialize()
  {
    $this->lc = WebIOStateConfig::instance();

    $this->today      = date('Y-m-d');
    $this->time_start = date('H:i');

    $this->logs = new WebIoLogModel();
  }

  function finalize()
  {
  }

  public array $jsonArray = [];

  function runtimeLightDirectionJSON($engine)
  {
    $areas = array();
    $engine->areas->getAllAreasData($areas);
    $platz = [];
    foreach ($areas as $area) {
      $platz[] = $area['area_id'];
    }
    $this->runtimeLightDirection($engine, $platz, 1, 1,true);

    return $this->jsonArray;
  }

  /** Сервис
   * $order[1] - [0 = прошел][1 = заблокирован][2 = одиночный заказ][3 = абонемент][4 = свет][5 = отопление][6 = сеть][7 = статус заказа]
   *
   * @param $engine Engines
   * @param $list_input_area_id
   * @param $type
   *
   * @return bool|string
   */
  function runtimeLightDirection($engine, $list_input_area_id, $type, $numberDays = 1, $json = false)
  {
    $numberDays = max($numberDays, 1);
    $order_array = array();
    foreach ($list_input_area_id as $input_area_id) {
      // >>> найти type_id по area_id
      if ($engine->areas->getAreaData($input_area_id, $area_data)) {
        for ($iDay = 0; $iDay < $numberDays; $iDay++) {
          $day = date('Y-m-d', strtotime($this->today . ' + ' . $iDay . ' day'));
          //для начала выбираем часы заботы за сегодня
          if ($engine->getPeriodsByAreasTypeDate(
              $area_data['type_id'],
              null,
              $day,
              $areas_data,
              $error_code,
              $area_data['sport_id']
            ) == true) {
            foreach ($areas_data as $area_id => $area) {
              if ($input_area_id == $area_id) {
                if ($area[1] != '10') {
                  continue;
                }
                if (is_array($area[2])) {
                  foreach ($area[2] as $start => $order) {
                    //Включение/выключение света
                    if ((
                        ($order[1][1] == '1' && $order[2]['block_data']['use_webIo']) // блокировка с функцией use_webIo
                        || $order[1][2] == '1' // бронирование
                        || $order[1][3] == '1' // абонимент
                      )
                      && $this->checkStateOrder($type, $order[1])) {
                      $weekday = CalendarHelper::getWeekdayByUnixtime(strtotime($day . ' ' . $this->time_start));
                      if ($order[1][3] == '1' && AreasLightsModel::checkAreasDefaultState(
                          $area_id,
                          $weekday,
                          $this->time_start,
                          $type
                        )) {
                        $engine->tickets->insertLightHeatingTicket(
                          $order[2]['ticket_id'],
                          $type,
                          $day,
                          $this->time_start,
                          $weekday
                        );
                      }
                      if ($json && $type == 1) {
                        $this->jsonArray[] = array(
                          "court_id" => "$area_id",
                          "start"    => date("Y-m-d\T") . $start . ':00Z',
                          "end"      => date("Y-m-d\T") . $order[0] . ':00Z',
                          'blocked'  => ($order[1][1] == 1) ? true : false,
                          "name"     => ($order[1][1] == 1)
                            ? $order[2]['block_data']['title']
                            : ($order[2]['client']['2'] . ' ' . $order[2]['client']['1'])
                        );
                      }
                      $order_array[$area_id . '_' . $type][$day][$start] = $order;
                    }
                  }
                } else {
                  return false;
                }
              }
            }
          } else {
            return false;
          }
        }
      } else {
        return false;
      }
    }
    $physic_state = array();
    foreach ($order_array as $area_type => $days) {
      foreach ($days as $day=> $times_start) {
        foreach ($times_start as $start => $order) {
          $title      = '';
          $clientName = $order[1][1] == '1'
            ? $order[2]['block_data']['title']
            : $order[2]['client'][1] . ' ' . $order[2]['client'][2];

          $titleEvent = $order[1][1] == '1'
            ? lang('blocking', 'reservation.light')
            : ($order[1][2] == '1' ? lang('reservations', 'reservation.light') : ($order[1][3] == '1' ? lang('abo', 'reservation.light') : ''));

          $title .= $this->lc->types[$type]->title . ' (' . $clientName . ' [' . $titleEvent . '])';
          $title .= ', ';
          $title = trim($title, ", ");
          if ($title !== '') {
            $physic_state[$area_type][] = array(
              $start,
              $order[0],
              $title,
              $day,
            );
          }
        }
      }
    }

    return $this->generateIcalCode($physic_state);
  }

  function generateIcalCode($physic_state = array())
  {
    foreach ($physic_state as $area_type => $schedule) {
      list($area, $type) = explode('_', $area_type);
      $output = $this->lc->getOutput($area, $type);
      if (is_array($schedule)) {
        $out                     = 'BEGIN:VCALENDAR' . "\n";
        $out                     .= 'PRODID:-//Forumedia iCal for Web I/O v.1.0//EN' . "\n";
        $previus_datetime_finish = false;
        foreach ($schedule as $s) {
          $outEvent = '';
          $unix_datetime_start = strtotime($s[3] . ' ' . $s[0]);

          //хитрая система пропуска, если есть вычитание времени
          if ($unix_datetime_start != $previus_datetime_finish) {
            $unix_datetime_start = mktime(
              date('H', $unix_datetime_start),
              (date('i', $unix_datetime_start) - $output->pre_start_time),
              0,
              date('m', $unix_datetime_start),
              date('d', $unix_datetime_start),
              date('Y', $unix_datetime_start)
            );
          }

          $unix_datetime_finish    = strtotime($s[3]  . ' ' . $s[1]);
          $previus_datetime_finish = $unix_datetime_finish;
          $outEvent                     .= 'BEGIN:VEVENT' . "\n";
          $outEvent                     .= 'SUMMARY: ' . $s[2] . "\n";
          $outEvent                      .= 'DTSTART:' . date('Ymd', $unix_datetime_start) . 'T' . date(
              'His',
              $unix_datetime_start
            ) . "\n";
         $outEvent                     .= 'DTEND:' . date(
             'Ymd',
              mktime(
                date('H', $unix_datetime_finish),
                date('i', $unix_datetime_finish),
                date('s', $unix_datetime_finish),
                date('m', $unix_datetime_finish),
                (date('Hi', $unix_datetime_finish) == '0000' ? (date('d', $unix_datetime_finish) + 1) : date(
                  'd',
                  $unix_datetime_finish
                )),
                date('Y', $unix_datetime_finish)
              )
            ) . 'T' . date(
              'His',
              $unix_datetime_finish
            ) . "\n";
          $outEvent                     .= 'END:VEVENT' . "\n";
          $events[] = $outEvent;
        }
        $out .= implode('', $events);
        $out .= 'END:VCALENDAR' . "\n";
      }
    }

    return $out;
  }

  //отправить запрос на девайс
  //$output - номер выхода на устройстве
  //$status: check - проверить вкл или выкл
  //		ON - включить
  //		OFF - выключить
  function sendSock($output, $status, &$error, $webIoId = 1)
  {
    $error   = false;
    $logSave = true;
    /** @var $webIo WebIoModel */
    $webIo = $this->lc->webIo[$webIoId];

    $this->logs->webio_id = $webIoId;
    $this->logs->date     = date('Y:m:d H:i:s');
    $this->logs->output   = $output;


    if ($status == 'check') {
      $link    = "http://" . $webIo->ip . ":" . $webIo->port . "/output" . $output . "?PW=" . $webIo->password . '&';
      $logSave = false;
    } else {
      if ($status == 'ON' || $status == 'OFF') {
        $this->logs->status = $status;

        $link = "http://" . $webIo->ip . ":" . $webIo->port . "/outputaccess" . $output . "?PW=" . $webIo->password . "&State=" . $status . '&';
      } else {
        //запись логов
        $this->logs->status  = 'Error';
        $this->logs->result  = 'error';
        $this->logs->comment = 'Status not set';
      }
    }

    if (!empty($link) && !($status = $this->sendWebIO($link, $error))) {
      //запись логов
      $this->logs->result  = 'error';
      $this->logs->comment = 'Connection failed:' . $error;
    } else {
      $status              = explode(';', $status);
      $status              = $status[1] ?? '';
      $this->logs->result  = 'ok';
      $this->logs->comment = '';
    }
    if ($logSave) {
      //запись логов
      $this->logs->save();
    }

    return $status;
  }

  function checkSock($webIoId = 1)
  {
    /** @var $webIo WebIoModel */
    $webIo = $this->lc->webIo[$webIoId];
    $link  = "http://" . $webIo->ip . ":" . $webIo->port;

    $status = $this->sendWebIO($link, $error);

    if ($status) {
      return true;
    }

    return false;
  }

  public function checkSendSockOutput($stringHex, $output, $status): bool
  {
    $state = (hexdec($stringHex) & pow(2, $output)) == pow(2, $output);
    return match ($status) {
      'ON'    => $state == true,
      'OFF'   => $state == false,
      default => $state
    };
  }

  public function sendWebIO($url = null, &$error = null)
  {
    $ch      = curl_init();
    $options = array(
      CURLOPT_URL            => $url,
      CURLOPT_RETURNTRANSFER => true,     // return web page
      CURLOPT_HEADER         => false,    // don't return headers
      CURLOPT_ENCODING       => "",       // handle all encodings
      CURLOPT_USERAGENT      => "webio",  // who am i
      CURLOPT_CONNECTTIMEOUT => 20,      // timeout on connect
      CURLOPT_TIMEOUT        => 20,      // timeout on response
      CURLOPT_MAXREDIRS      => 1,        // stop after 10 redirects
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
    );
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);

    if ($data == false) {
      $error = 'ERROR ' . $url;
    } else {
      $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($http_status != 200) {
        $error = 'ERROR ' . $url . ' status ' . $http_status;
      }
    }
    curl_close($ch);

    return $data;
  }

//манипуляции со светов по area_id
  function areaLightProcess($area_id, $type, $status, &$error)
  {
    if ($output = $this->lc->getOutput($area_id, $type)) {
      if ($st = $this->sendSock($output->port, $status, $error, $output->webio_id)) {
        return $st;
      }
    }

    return false;
  }

  function areasElectricityProcess($areas, &$error)
  {
    //готовим запрос для включения
    foreach ($areas as $area_id => $ports) {
      if (is_array($ports)) {
        foreach ($ports as $type => $state) {
          $output = $this->lc->getOutput($area_id, $type);
          $this->sendSock($output->port, ($state == 1 ? 'ON' : 'OFF'), $error, $output->webio_id);
        }
      }
    }

    return true;
  }

  /** Сделать проверку используется ли свет, тепло или сетка для данного промежутка
   *
   * @param $type - тип webIo
   * @param $state_bit - побитовый строка с данными
   *                   [0 = прошел][1 = заблокирован][2 = одиночный заказ][3 = абонемент][4 = свет][5 = отопление][6 = сеть][7 = статус заказа]
   *
   * @return bool
   */
  public function checkStateOrder($type, $state_bit)
  {
    switch (1) {
      case (int)($type == 1 && $state_bit[4] == '1') :
      case (int)($type == 2 && $state_bit[5] == '1') :
      case (int)($type == 3 && $state_bit[6] == '1') :
      case (int)(!$this->lc->types[$type]->use_reservation && $this->lc->types[$type]->active) :
        return true;
      default :
        return false;
    }
  }

  public function insertBase($ip, $port, $password, $use, $pre_start_time, $name = '', $count_ports = 12)
  {
    $query = 'insert into ' . Query::tableName($this->table) . ' (`ip`, `port`, `password`, `use`, `pre_start_time`, `name`, `count_ports`) 
    value (:ip, :port, :password, :use, :pre_start_time, :name, :count_ports)';
    Query::sqlQuery(
      $query,
      array(
        ':ip'             => $ip ?? '',
        ':port'           => $port ?: null,
        ':password'       => $password ?? '',
        ':use'            => (int)$use,
        ':pre_start_time' => $pre_start_time,
        ':name'           => $name,
        ':count_ports'    => $count_ports
      ),
      false
    );

    return Query::getLastId();
  }

  /** Установить в новые таблицы данные света
   * @throws ReflectionException
   */
  private function setConfig()
  {
    if ((int)Service::configDB('webIo', 'webIo_use')
      && (!Query::getDB()->checkTable(Query::tableName($this->table), false)
        || !count(Query::sqlQuery('select * from ' . Query::tableName($this->table)))
      )) {
      $config    = new stdClass();
      $setConfig = true;
      $_config   = ConfigModel::getByType('webIo');

      foreach ($_config as $key => $value) {
        $key            = str_replace('webIo_', '', $key);
        $config->{$key} = $value;
        if (in_array($key, array('ip', 'port')) && empty($value)) {
          $setConfig = false;
        }
        ConfigModel::removeByTypeAndAlias('webIo', 'webIo_' . $key);
      }
      if ($setConfig) {
        $webIo_id = $this->insertBase($config->ip, $config->port, $config->password, $config->use, $config->pre_start_time);
        $outputs  = array();
        foreach (JsonHelper::decode($config->output, true) as $port => $item) {
          if (!is_array($item['area'])) {
            $item['area'] = array($item['area']);
          }
          foreach ($item['area'] as $area_id) {
            $outputs[] = array(
              'port'          => $port,
              'area_id'       => $area_id,
              'webio_type_id' => $item['type'],
            );
          }
        }
        WebIoOutputsModel::insert($webIo_id, $outputs);
      }
      $a = new AreasEngine('');
      WebIoTypeSatesModel::setActive('alias', $a->getAreasWebIoTypeStateOn());
    }
  }

}

?>