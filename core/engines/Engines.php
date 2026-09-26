<?php

namespace AC\core\engines;

use AC\core\modules\holidays\engines\HolidaysEngine;
use AC\core\modules\reservations\entities\dto\ReservationDto;
use AC\core\modules\reservations\services\ArchivedReservationDataDecoder;
use AC\core\modules\specPrices\engines\SpecPricesEngine;
use AC\core\system\db\Query;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\TranslateHelper;
use Exception;
use PDO;
use Service;

useClass('engines\reservation');

/**
 * Class Engines
 *
 */
class Engines extends \reservation
{
  /**
   * @var HolidaysEngine
   */
  public $holidays;
  /**
   * @var BarEngine
   */
  public $bar;
  /**
   * @var ClientsEngine
   */
  public $clients;

  /**
   * @var AreasEngine
   */
  public $areas;
  /**
   * @var SportsEngine
   */
  public $sports;
  /**
   * @var StocksEngine
   */
  public $stocks;
  /**
   * @var SpecPricesEngine
   */
  public $specprice;
  /**
   * @var BlocksEngine
   */
  public $blocks;
  /**
   * @var TicketsEngine
   */
  public $tickets;
  /**
   * @var DoorCodesEngine
   */
  public $door_code;
  /**
   * @var StatisticsEngine
   */
  public $statistics;
  /**
   * @var WebIoEngine
   */
  public $webIo;
  /**
   * @var WebIoEngine
   */
  public $light;
  /**
   * @var PpEngine
   */
  public $pp;
  /**
   * @var DiscountsEngine
   */
  public $discount;
  /**
   * @var ExtraEngine
   */
  public $extra;
  /**
   * @var NdsEngine
   */
  public $nds;
  /**
   * @var CouponEngine
   */
  public $coupons;

  public $config;
  public $unixtime_current_date;
  public $unixtime_max_orderable;
  public $rules;

  protected $typeAlias;

  public function __construct()
  {
    $this->initialize();
    $this->rules = new TableRules(null);
  }

  /* ИНТЕРФЕЙС */

  public function initialize()
  {
    parent::initialize();
//    $this->checkAndInstallTables(['reservations', 'reservation_data']);
//
//    //считываем конфигурационные значения
//    $temp = Query::sqlQuery('select * from ' . Query::tableName('config'));
//    foreach ($temp as $item) {
//      $this->config[$item['alias']] = $item['value'];
//    }
    $this->config = array_merge($this->config, ConfigEngine::getConfig());
    ConfigEngine::loadMinMaxParams($this->config);
    //текущая дата в unixtime
//    $this->unixtime_current_date = strtotime(date('Y-m-d'));
    //макс. время в unixtime, на которое можно заказывать

    $this->areas  = new AreasEngine();
    $this->sports = new SportsEngine();

    $typeId = (int)Service::request()->_('type_id', $this->areas->getFirstActiveType());
    $this->areas->getTypeData($typeId, $type);
    $minSport                     = $this->areas->getMinSportByType($typeId);
    $this->unixtime_max_orderable = strtotime(
      date('Y-m-d') . ' + '
      . $this->config['min_max'][$typeId][Service::request()->_('sport_id', $minSport)]['max_forward_reservation_days_count'] . ' days'
    );

    $this->blocks = new BlocksEngine();
    $this->blocks->initialize();

    $this->clients = new ClientsEngine();
    $this->clients->initialize();

    $this->holidays = new HolidaysEngine();
    $this->holidays->initialize();

    $this->statistics = new StatisticsEngine();
    $this->statistics->initialize();

    $this->tickets = new TicketsEngine();
    $this->tickets->initialize();

    //акции
    $this->stocks = new StocksEngine();

    //спеццена
    $this->specprice = new SpecPricesEngine();

    //paypal
    $this->pp = new PpEngine();

    //Скидки
    $this->discount = new DiscountsEngine();

    //Наценки
    $this->extra = new ExtraEngine();

    $this->bar = new BarEngine();
    //НДС
    $this->nds = new NdsEngine();

    //Дверные коды
    $this->door_code = new DoorCodesEngine();

    //Свет
    $this->webIo = new WebIoEngine();
    $this->webIo->initialize();
    $this->light = new WebIoEngine();
    $this->light->initialize();

    //купоны
    $this->coupons = new CouponEngine();

    $this->instanceTypeAlias();
  }

  public function finalize()
  {
    $this->blocks->finalize();
    $this->clients->finalize();
    $this->coupons->finalize();
    $this->holidays->finalize();
    $this->statistics->finalize();
    $this->tickets->finalize();
  }

  public function getMinDateReservation(): int
  {
    static $min;
    if (is_null($min)) {
      $temp = Query::sqlQuery('SELECT min(`start`) AS `min` FROM ' . Query::tableName('reservations'), [], true, ['onlyOne' => true]);
      $min  = !empty($temp['min']) ? strtotime($temp['min']) : strtotime('-4 years');
    }

    return $min;
  }

  public function getMinDateAbo(): int
  {
    static $min;
    if (is_null($min)) {
      $temp = Query::sqlQuery('SELECT min(`start`) AS `min` FROM ' . Query::tableName('tickets_periods'), [], true, ['onlyOne' => true]);
      $min  = !empty($temp['min']) ? strtotime($temp['min']) : time();
    }

    return $min;
  }

  /* ЗАКАЗЫ */

  //добавить
  //true/false
  /**
   * @param ReservationDto $reservationDto
   * @param bool           $online_pay
   * @param int            $error_code
   *
   * @return bool
   */
  function insertReservation(ReservationDto $reservationDto, $online_pay = false, &$error_code = 0)
  {
    if ($this->checkAreaDateTimeAvailable(
      $reservationDto->areaId,
      $reservationDto->clientId,
      $reservationDto->start->format('Y-m-d'),
      $reservationDto->start->format('H:i:s'),
      $error_code
    )) {
      $tableName        = 'reservations' . ($online_pay ? '_tmp_paypal' : '');
      $hasCustomerTitle = Service::query()::getDB()->checkField('customer_title', $tableName);
      $hasPayOneType    = Service::query()::getDB()->checkField('pay_one_type', $tableName);
      $hasPaypalStatus  = Service::query()::getDB()->checkField('paypal_status', $tableName);
      $q                = 'insert into ' . Query::tableName($tableName) . ' set ' .
        'area_id = :area_id, ' .
        'client_id = :client_id, ' .
        'payment_state = :payment_state, ' .
        'status = :status, ' .
        'main_client_id = :main_client_id, ' .
        'main_reservation_id = :main_reservation_id, ' .
        'light_state = :light_state, ' .
        'heating_state = :heating_state, ' .
        'net_state = :net_state, ' .
        'type_reservation = :type_reservation, ' . // одиночное или двойное бронирование открытые корты
        'street_friends = :street_friends, ' . // присоеденные игроки
        'stock_id = :stock_id,' .
        'sprice_id = :sprice_id, ' .
        'encash = :encash, ' .
        'start = :start, ' .
        'finish =:finish, ' .
        'client_name = :client_name, ' .
        'client_surname = :client_surname, ' .
        'price = :price, ' .
        'light_price = :light_price, ' .
        'heating_price = :heating_price, ' .
        'net_price = :net_price, ' .
        'customer = :customer, ' .
        'door_code = :door_code, ' .
        'memo = :memo, ' .
        'ordered = now()'
        . ($hasCustomerTitle ? ', customer_title = :customer_title' : '')
        . ($hasPayOneType ? ', pay_one_type = :pay_one_type' : '')
        . ($hasPaypalStatus ? ', paypal_status = :paypal_status' : '');
      $param            = [
        'area_id'             => $reservationDto->areaId,
        'client_id'           => $reservationDto->clientId,
        'payment_state'       => $reservationDto->paymentState->value,
        'status'              => $reservationDto->status->value,
        'main_client_id'      => $reservationDto->mainClientId,
        'main_reservation_id' => $reservationDto->mainReservationId,
        'light_state'         => $reservationDto->lightState->value,
        'heating_state'       => $reservationDto->heatingState->value,
        'net_state'           => $reservationDto->netState->value,
        'type_reservation'    => $reservationDto->typeReservation->value,
        // одиночное или двойное бронирование открытые корты
        'street_friends'      => !empty($reservationDto->streetFriends) ? Service::cast('array')->set($reservationDto->streetFriends) : null,
        // присоеденные игроки
        'stock_id'            => $reservationDto->stockId,
        'sprice_id'           => $reservationDto->spriceId,
        'encash'              => $reservationDto->encash->value,
        'start'               => $reservationDto->start->format('Y-m-d H:i:s'),
        'finish'              => $reservationDto->finish->format('Y-m-d H:i:s'),
        'client_name'         => $reservationDto->clientName,
        'client_surname'      => $reservationDto->clientSurname,
        'price'               => max($reservationDto->price, 0),
        'light_price'         => max($reservationDto->lightPrice, 0),
        'heating_price'       => max($reservationDto->heatingPrice, 0),
        'net_price'           => max($reservationDto->netPrice, 0),
        'customer'            => $reservationDto->customer,
        'door_code'           => $reservationDto->doorCode,
        'memo'                => $reservationDto->memo,
      ];
      if ($hasCustomerTitle) {
        $param['customer_title'] = $reservationDto->customerTitle;
      }
      if ($hasPayOneType) {
        $param['pay_one_type'] = $reservationDto->payOnlineType;
      }
      if ($hasPaypalStatus) {
        $param['paypal_status'] = $reservationDto->paypalStatus;
      }
      Query::beginTransaction();
      if (Query::sqlQuery($q, $param, false) && $reservationId = Query::getLastId()) {
        $reservation_id     = !$online_pay ? $reservationId : null;
        $tmp_reservation_id = $online_pay ? $reservationId : null;
        if ((!$data = $reservationDto->asArrayByReservationDataDto())
          || $this->reservationDataEngine()->insertReservationData($data, $reservation_id, $tmp_reservation_id)) {
          Query::commit();

          return $reservationId;
        }
      }
      Query::rollback();
    }

    return false;
  }

  //Присоседиваем клиента (в итоге клиент играет с клиентом)
  function joinReservation($reservation_id, $status, $price, $client2_id, $client2_name)
  {
    $q1 = 'update ' . Query::tableName('reservations') . ' r set
					    r.status = :status' .
      ($price ? ', r.price = (r.price + :price) ' : '') . '
					    where r.reservation_id = :reservation_id';

    $q2 = 'update ' . Query::tableName('reservations') . ' r set
					    r.status = :status,
					    r.client_id = :client_id,
					    r.client_name = :client_name 
					    where r.main_reservation_id = :main_reservation_id';

    $param1 = [
      ':status' => $status,
    ];
    if ($price) {
      $param1[':price'] = $price;
    }
    $param1[':reservation_id'] = $reservation_id;

    $param2 = [
      ':status'              => $status,
      ':client_id'           => ($client2_id === null ? null : $client2_id),
      ':client_name'         => ($client2_id === null ? $client2_name : null),
      ':main_reservation_id' => $reservation_id,
    ];

    $f1 = Query::sqlQuery($q1, $param1, false);
    $f2 = Query::sqlQuery($q2, $param2, false);
    //Если запрос принесет результат возвращаем true
    if ($f1 && $f2) {
      return true;
    }

    //Если нет, то возвращаем false
    return false;
  }

  //Отсоединяем бронь для клиента который присоседился к другому клиенту
  function unjoinReservation($reservation_id, $status, $price)
  {
    $f1 = Query::sqlQuery(
      'update ' . Query::tableName('reservations') . ' r set
					    r.status = "' . $status . '" ' .
      ($price ? ', r.price = r.price - ' . $price . ' ' : '') . '
					    where r.reservation_id = "' . $reservation_id . '"',
      [],
      false
    );

    $f2 = Query::sqlQuery(
      'update ' . Query::tableName('reservations') . ' r set
					    r.status = "' . $status . '",
					    r.client_id = null,
					    r.client_name = null
					    where r.main_reservation_id = "' . $reservation_id . '"',
      [],
      false
    );

    if ($f1 && $f2) {
      return true;
    }

    return false;
  }


  /** получить количество дней бронирования
   *
   * @param $type_id
   *
   * @return int
   */
  public function getCountDaysForwardReservation($type_id = 1, $sport_id = 1)
  {
    return $this->config['min_max'][$type_id][$sport_id]['max_forward_reservation_days_count'];
  }


  /** Проверить возможна ли бронь для пользователя, по клубный ограничениям
   *
   * @param              $rule
   * @param              $times
   * @param              $date
   *
   * @return bool
   */
  public function checkThePossibilityOfReservationByClubRule(
    $rule,
    $times,
    $date,
  ) {
    $check = 0;
    foreach ($times as $time) {
      if (!isset($rule['time_limit']) || !$rule['time_limit'] ||
        ($date == date('Y-m-d', strtotime('now')) && $rule['time_limit'] && strtotime($time) >= strtotime($rule['time_start'])
          && strtotime($time) < strtotime($rule['time_finish']))) {
        $check++;
      }
    }
    if (count($times) === $check) {
      return true;
    }


    return false;
  }

  public function getTypes()
  {
    return ['close' => 'Halle', 'open' => 'Freiplätze', 'mc_arena' => 'McArena'];
  }

  public static function getTitleEncash($encash = null)
  {
    switch ($encash) {
      case 0:
        return 'BAR';
        break;
      case 1:
        return 'RE';
        break;
      case 2:
        return 'GH';
        break;
      case 3:
        return 'PP';
        break;
      case 4:
        return 'EC';
        break;
      default :
        return null;
        break;
    }
  }

//данные о резервировани по заданной площадке и unixtime
  public function changePaymentState($reservation_id, $state)
  {
    if (Query::sqlQuery(
      'update ' . Query::tableName(
        'reservations'
      ) . ' set payment_state = "' . $state . '" where reservation_id = "' . $reservation_id . '"',
      [],
      false
    )) {
      return true;
    }

    return false;
  }

//данные о резервировани по заданной площадке и unixtime для группы
  public function getReservationGroupData(
    $area_id,
    $client_id,
    $mysql_date,
    $mysql_time,
    &$reservation_data,
  ) {
    $q                = 'SELECT rr.* FROM ' . Query::tableName('reservations') . ' rr
					WHERE rr.start >= (
								SELECT r.start FROM ' . Query::tableName('reservations') . ' r
								LEFT JOIN ' . Query::tableName(
        'reservations'
      ) . ' r1 ON (r1.finish = r.start AND r1.client_id = "' . $client_id . '" AND r1.area_id = "' . $area_id . '" )
								WHERE r.client_id = "' . $client_id . '" AND r.area_id = "' . $area_id . '" AND r.start <= "' . $mysql_date . ' ' . $mysql_time . '" AND r.start >= "' . $mysql_date . ' 00:00" AND r1.reservation_id IS NULL
								GROUP BY r.reservation_id ORDER BY r.start DESC LIMIT 1
							)
					AND rr.start <= "' . $mysql_date . ' ' . $mysql_time . '" AND
					rr.client_id = "' . $client_id . '" AND rr.area_id = "' . $area_id . '" order by rr.start';
    $reservation_data = Query::sqlQuery($q);

    if (!empty($reservation_data)) {
      //получаем строки таблицы

      return true;
    } else {
      //запись о резервировании не найдена
      $reservation_data = null;

      return false;
    }
  }

  public function changeReservationPrice(
    $reservation_id,
    $price,
    $encash = false,
  ) {
    if (Query::sqlQuery(
      'update ' . Query::tableName('reservations') . ' set price = "' . $price . '" ' . ($encash !== false ? ', encash = "' . $encash . '"'
        : '') . ' where reservation_id = ' . $reservation_id,
      [],
      false
    )) {
      return true;
    }

    return false;
  }

  public function getTypeAliasByTypeId($type_id)
  {
    $this->areas->getTypeData((int)$type_id, $areas_type);

    return $areas_type['alias'];
  }

  public static function getTypeClientByMode($mode, $guest_view = true)
  {
    return match ((int)$mode) {
      1       => $guest_view ? 'N' : 'online',
      2       => $guest_view ? 'N|OFF' : 'offline',
      3       => $guest_view ? 'J' : 'gast',
      default => null,
    };
  }

  public static function getTypeTitleForReport($mode_encash)
  {
    [$mode, $encash] = explode('_', $mode_encash);
    $mode_encash = $mode > 1 ? $mode : $mode_encash;
    switch ($mode_encash) {
      case '1_0':
      case '1_2':
        return lang('Online customers with login (cash payment)', 'reports');
        break;
      case '1_1':
        return lang('Online customers with login (invoice)', 'reports');
        break;
      case '1_3':
        return lang('PayPal', 'reports');
        break;
      case '1_4':
        return lang('EC', 'reports');
        break;
      case '2':
        return lang('Offline clients without login', 'reports');
        break;
      case '3':
        return lang('Cash payer without login', 'reports');
        break;
      default:
        return '';
        break;
    }
  }

  public $cashLastReserv;
  public $cashAreaData;

  /** todo  не фига не работает как нужно первоисточник tennisanlage-berenbostel.de не учитываются например блокировки */
  public function getLastReserv($date, $time, $type_id, $area_id, $touch = false)
  {
    $areas = defined('USE_SHOW_ONE_AVAILABLE_PERIOD_ON_TOUCH_IN_OPEN_COURT') && USE_SHOW_ONE_AVAILABLE_PERIOD_ON_TOUCH_IN_OPEN_COURT
      ? explode(';', USE_SHOW_ONE_AVAILABLE_PERIOD_ON_TOUCH_IN_OPEN_COURT)
      : [];

    if (!config('reservations')->isOpenType((int)$type_id) || !in_array($area_id, $areas) || !$touch) {
      return true;
    }
    $reserv = [];
    if (isset($this->cashLastReserv[$area_id])) {
      $reserv = $this->cashLastReserv[$area_id];
    } else {
      $query = 'SELECT r.* FROM ' . Query::tableName('reservations')
        . ' r WHERE r.start>="' . date(
          'Y-m-d H:i:s',
          strtotime('now')
        ) . '" and r.area_id="' . $area_id . '" ORDER BY "DESC" LIMIT 0,1 ';
      $temp  = Query::sqlQuery($query);

      if (!empty($temp)) {
        //получаем строки таблицы
        foreach ($temp as $item) {
          $reserv                         = $item;
          $this->cashLastReserv[$area_id] = $item;
        }
      }
    }

    $area = [];
    if (!isset($this->$cashAreaData[$area_id])) {
      $this->cashAreaData[$area_id] = [];
      $this->areas->getAreaData($area_id, $this->cashAreaData[$area_id]);
    }
    $area = $this->cashAreaData[$area_id];

    $abos = $this->getAbos(date('Y-m-d H:i:s', strtotime($date)), (int)$type_id);
    $max  = '0000';
    foreach ($abos[$area_id] as $abo) {
      if ($abo[1] > $max) {
        $max = $abo[1];
      }
    }
    $abo_time = substr_replace($max, ":", 2, 0);
    $abo_max  = strtotime($date . ' ' . $abo_time) + $area['offset_max'] * 60 - OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION * 60;

    $timestart = isset($reserv['start']) ?
      (strtotime($reserv['start']) + OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION * 60)
      : strtotime('now');
    $timestart = ($abo_max > $timestart) ? $abo_max : $timestart;
    $unix_time = strtotime($date . ' ' . $time);
    if (($timestart - $unix_time > 0) || ($timestart - $unix_time < -OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION * 60)) {
      return false;
    }

    return true;
  }


  public function checkingForReservationsBasedOnTimeConditions($date, $time, $type_id, &$error_message = '', $touch = false, $sport_id = 1)
  {
    $unix_time         = strtotime($date . ' ' . $time);
    $now               = strtotime('now');
    $real_interval     = abs($unix_time - $now);
    $time_interval_min = $this->getTimeIntervalMin($type_id);
    $time_interval_max = $time_interval_min + (OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION
        ? OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION * 60 : 0);
    if (config('reservations')->isOpenType((int)$type_id) && (OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION
        && $real_interval > $time_interval_max)) {
      // можно забранировать только заданый промежуток от текущего времени - только открытые корты
      $hours   = 0;
      $minutes = OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION;
      if (OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION > 60) {
        $hours   = floor(OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION / 60);
        $minutes = OPEN_TIME_INTERVAL_IN_MINUTE_IN_WHICH_CAN_RESERVATION - ($hours * 60);
      }

      $error_message = lang('error about booking not earlier than a certain time', 'message_error', [
        'hour'   => ($hours > 0 ? $hours . ' ' . lang('Hours') . ' ' : ''),
        'minute' => ($minutes > 0
          ? $minutes . ' ' . lang('Minutes') . ' ' : ''),
      ]);

      return false;
    } elseif ($time_interval_min
      && $real_interval < $time_interval_min) {
      // можно сделать бронирование только за время после указанного промежутка - только открытые корты
      $error_message = lang(
        'You can only book the time after hours from the current one.',
        'message_error',
        ['interval' => TranslateHelper::translatePeriodInMinute($time_interval_min / 60)]
      );

      return false;
    } elseif ($real_interval > $this->unixtime_max_orderable) {
      // можно бронировать опеределенное количество дней заданых в админке
      $error_message = lang('These seats are bookable for you days before the start of the game at the earliest!', 'message_error', [
        'interval' => $this->getCountDaysForwardReservation(
          $type_id,
          $sport_id
        ),
      ]);

      return false;
    }

    return true;
  }

  public function getTimeIntervalMin($type_id): int
  {
    return (int)match ($type_id) {
        2       => OPEN_TIME_INTERVAL_IN_MINUTE_AFTER_WHICH_RESERVATION_IS_POSSIBLE,
        default => TIME_INTERVAL_IN_HOUR_AFTER_WHICH_RESERVATION_IS_POSSIBLE
      } * 60 * 60;
  }

  public function getStreetFriendsReservationsById(
    $client_id,
    $date1,
    $date2 = false,
    $type_id = false,
    $sport_id = false,
  ) {
    $data = ['all' => [], 'main' => []];
    if ($date2) {
      $sqltime = " between \"$date1\" AND \"$date2\"";
    } else {
      $sqltime = ">\"$date1\"";
    }
    $sql  = 'SELECT * FROM ' . Query::tableName('reservations') . " r inner join  " . Query::tableName('areas') . " a on r.area_id = a.area_id 
        WHERE r.street_friends LIKE '%\"id\";i:" . (int)$client_id . ";%' and r.`start`$sqltime "
      . ($type_id ? ' AND a.type_id = ' . (int)$type_id . ' ' : '') . '
            ' . ($sport_id ? ' AND a.sport_id = ' . (int)$sport_id . ' ' : '');
    $temp = Query::sqlQuery($sql);
    foreach ($temp as $item) {
      if (empty($item['main_reservation_id'])) {
        $data['main'][] = $item;
      }
      $data['all'][] = $item;
    }

    return $data;
  }

  public function getStreetFriendsReservationsByIdOld(
    $client_id,
    $date1,
    $date2 = false,
    $type_id = false,
    $sport_id = false,
  ) {
    $data = ['all' => [], 'main' => []];
    if ($date2) {
      $sqltime = " between \"$date1\" AND \"$date2\"";
    } else {
      $sqltime = ">\"$date1\"";
    }
    $sql  = 'SELECT * FROM ' . Query::tableName('reservations') . " r inner join  " . Query::tableName('areas') . " a on r.area_id = a.area_id 
        WHERE client_id='" . (int)$client_id . "' and main_reservation_id is not null and `start`$sqltime "
      . ($type_id ? ' AND a.type_id = ' . (int)$type_id . ' ' : '') . '
            ' . ($sport_id ? ' AND a.sport_id = ' . (int)$sport_id . ' ' : '');
    $temp = Query::sqlQuery($sql);
    foreach ($temp as $item) {
      if (empty($item['main_reservation_id'])) {
        $data['main'][] = $item;
      }
      $data['all'][] = $item;
    }

    return $data;
  }

  public function getReservationType($datetime, $area_id, &$type_reservation)
  {
    $sql  = 'SELECT a.type_reservation FROM ' . Query::tableName(
        'reservations'
      ) . ' as a where a.area_id="' . $area_id . '" and a.start="' . $datetime . '"';
    $temp = Query::sqlQuery($sql);
    if (!empty($temp[0])) {
      return $type_reservation = $temp[0]['type_reservation'];
    } else {
      return false;
    }
  }

  public static function checkShowTitleSport($type_id, $sport_id)
  {
    return !(defined('NOT_SHOW_TITLE_SPORT_SITE_URL')
      && NOT_SHOW_TITLE_SPORT_SITE_URL
      && (strpos(NOT_SHOW_TITLE_SPORT_SITE_URL, $type_id . '_*') !== false
        || strpos(NOT_SHOW_TITLE_SPORT_SITE_URL, $type_id . '_' . $sport_id) !== false
      ));
  }

  public static function checkShowTitleSportToType($type_id, $sport_id)
  {
    return !(defined('NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL')
      && NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL
      && (strpos(NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL, $type_id . '_*') !== false
        || strpos(NOT_SHOW_TITLE_TYPE_SPORT_SITE_URL, $type_id . '_' . $sport_id) !== false
      ));
  }

  public function setStatusReservations($reservation_id, $status)
  {
    return Query::sqlQuery(
      'update ' . Query::tableName('reservations') . ' set status=:status where reservation_id=:id or main_reservation_id=:id2',
      [':status' => $status, ':id' => $reservation_id, ':id2' => $reservation_id]
    );
  }

  public function removeReservationById($id)
  {
    [$client_id, $reservation_id] = Query::sqlQuery(
      'select client_id, reservation_id from ' . Query::tableName('reservations') . ' where reservation_id=:id ',
      [':id' => $id],
      true,
      ['onlyOne' => true, 'style' => PDO::FETCH_NUM]
    );

    if ($id == $reservation_id) {
      $this->addReservationDataInArchive($id, true);
      Query::sqlQuery(
        'delete from ' . Query::tableName('reservations') . ' where reservation_id=:id or main_reservation_id=:id2',
        [':id' => $id, ':id2' => $id],
        false
      );
      getEngine('ReservationData')?->removeReservationData($id);

      if ($client_id !== null) {
        //инкрементим счетчик удаленных заказов клиента
        Query::sqlQuery(
          'update ' . Query::tableName('clients') . ' set reservations_removed = reservations_removed + 1 where client_id = ' . $client_id,
          [],
          false
        );
      }

      return true;
    }

    return false;
  }


  //удалить непосредственное резервирование
  function removeReservation($area_id, $mysql_datetime)
  {
    if ($this->getReservationData($area_id, $mysql_datetime, $reservation_data)) {
      return $this->removeReservationById($reservation_data['reservation_id']);
    }

    return false;
  }

  public function addReservationDataInArchive($reservation_id, $withSubordinates = false): void
  {
    $placeHolders = [':id' => $reservation_id];
    if ($withSubordinates) {
      $placeHolders[':id2'] = $reservation_id;
    }
    $query = 'select * from ' . Query::tableName('reservations')
      . ' where reservation_id=:id' . ($withSubordinates ? ' or main_reservation_id=:id2' : '');
    foreach (Query::sqlQuery($query, $placeHolders) as $item) {
      $item['street_friends'] = Service::cast('array')->get($item['street_friends']);
      $itemReservationId      = $item['reservation_id'];
      $reservationData        = JsonHelper::encode(array_merge(
        $item,
        $this->reservationDataEngine()->getReservationData($itemReservationId)
      ));

      Query::sqlQuery(
        'insert into ' . Query::tableName('reservations_deleted')
        . ' (`date_delete`, `reservation_id`, `main_reservation_id`, `client_id`, `main_client_id`, `area_id`, `start`, `reservation_data`)'
        . ' values (:date_delete, :reservation_id, :main_reservation_id, :client_id, :main_client_id, :area_id, :start, :reservation_data)',
        [
          ':date_delete'         => date('Y-m-d H:i:s'),
          ':reservation_id'      => $itemReservationId,
          ':main_reservation_id' => $item['main_reservation_id'] ?? null,
          ':client_id'           => $item['client_id'] ?? null,
          ':main_client_id'      => $item['main_client_id'] ?? null,
          ':area_id'             => $item['area_id'],
          ':start'               => $item['start'],
          ':reservation_data'    => $reservationData,
        ],
        false
      );
    }
  }

  public function getArchReservByUser($user_id)
  {
    $q      = 'SELECT *
    FROM ' . (Query::tableName('reservations_deleted')) . ' 
    WHERE `client_id` = ' . $user_id . ' AND `main_client_id` IS NULL ORDER BY `start` DESC';
    $reserv = Query::sqlQuery($q);

    $areas_title = $this->areas->getAreasTitles();
    $decoder = new ArchivedReservationDataDecoder();
    foreach ($reserv as $index => &$line) {
      try {
        $line['reservation_data'] = $decoder->decode($line['reservation_data']);
        $line['reservation_data']['areas_title'] = $areas_title[$line['area_id']];
      } catch (Exception $exception) {
        Service::logger('reservation_archive')->logException(
          $exception,
          'Archived reservation data could not be decoded',
          [
            'reservation_id' => (int)$line['reservation_id'],
            'client_id' => (int)$line['client_id'],
          ],
          'warning'
        );
        unset($reserv[$index]);
      }
    }
    unset($line);

    return array_values($reserv);
  }

  public function getTypeAlias()
  {
    return $this->typeAlias == null ? $this->instanceTypeAlias() : $this->typeAlias;
  }

  private function instanceTypeAlias()
  {
    $type_id = Service::request()->_('type_id', Service::session()->get('type_id'));

    $this->areas->selectActiveType();
    $this->typeAlias = $type_id !== null ? $this->getTypeAliasByTypeId((int)$type_id) : current($this->areas->getTypesAliasByArray());

    return $this->typeAlias;
  }


  public function checkTimeConfirmationOfReservations($time, $time_range = false)
  {
    if ($time_range) {
      if (is_string($time_range)) {
        $confTime = explode('_', $time_range);
      }
      $max_time_unix = strtotime($time . ($confTime[0] > 0 ? ' - ' : ' + ') . abs($confTime[0]) . ' minutes');
      $min_time_unix = strtotime($time . ($confTime[1] > 0 ? ' - ' : ' + ') . abs($confTime[1]) . ' minutes');
      $time_unix     = strtotime(date('H:i'));
      if ($min_time_unix <= $time_unix && $time_unix <= $max_time_unix) {
        return true;
      }
    }

    return false;
  }
}
