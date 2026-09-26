<?php

use AC\core\engines\PricingReservationEngine;
use AC\app\services\DataService;
use AC\core\engines\ExtraEngine;
use AC\core\engines\WebIoEngine;
use AC\core\modules\areas\models\AreasLightsModel;
use AC\core\modules\holidays\entities\enums\HolidayScheduleType;
use AC\core\modules\holidays\helpers\HolidayHelper;
use AC\core\modules\reservations\engines\ReservationDataEngine;
use AC\core\modules\reservations\helpers\TmpBlockingHelper;
use AC\core\modules\stocks\entities\dto\StockDto;
use AC\core\system\db\Query;
use AC\core\system\engine\InstallTableIfNotExist;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\RedisHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

useClass('engines\reservation.areas.php');     //площадки
useClass('engines\reservation.blocks.php');    //блокировки
useClass('engines\reservation.clients.php');   //клиенты
useClass('engines\reservation.holidays.php');  //праздниики
useClass('engines\reservation.statistics.php');//статистика
useClass('engines\reservation.tickets.php');   //билеты-абонементы
useClass('engines\reservation.stocks.php');    //акции
useClass('engines\reservation.specprice.php'); //спеццена
useClass('engines\reservation.pp.php');        //paypal
useClass('engines\reservation.discount.php');  //скидки
useClass('engines\reservation.nds.php');       //НДС
useClass('engines\reservation.doorcodes.php'); //Дверные коды
useClass('engines\reservation.light.php');     //Свет
useClass('engines\reservation.bar.php');
useClass('engines\reservation.pricing.php');

//класс по отработке логики резервирования
//коды ошибок
//10 - площадка не найдена
//20 - клиент не найден
class reservation
{

  /**
   * @var ExtraEngine
   */
  public $extra;
  /**
   * @var reservation_areas
   */
  public $areas;
  /**
   * @var reservation_clients
   */
  public $clients;

  /**
   * @var PricingReservationEngine
   */
  public $pricing;

  /**
   * @var reservation_holidays
   */
  public $holidays;

  /* ИНТЕРФЕЙС */
  use InstallTableIfNotExist;

  function initialize()
  {
    $this->checkAndInstallTables(['reservations', 'reservation_data']);

    //считываем концигурационные значения
    $q    = 'select * from ' . Query::tableName('config');
    $temp = Query::sqlQuery($q);
    foreach ($temp as $item) {
      $this->config[$item['alias']] = $item['value'];
    }

    //текущая дата в unixtime
    $this->unixtime_current_date = strtotime(date('Y-m-d'));
    //макс. время в unixtime, на которое можно заказывать вперед
    $this->unixtime_max_orderable = strtotime(
      date('Y-m-d') . ' + '
      . $this->config['max_forward_reservation_days_count'] . ' days'
    );

    $this->areas = new reservation_areas ();

    $this->blocks = new reservation_blocks ();

    $this->clients = new reservation_clients ();
    $this->clients->initialize();

    $this->holidays = new reservation_holidays ();
    $this->holidays->initialize();

    $this->statistics = new reservation_statistics ();
    $this->statistics->initialize();

    $this->tickets = new reservation_tickets ();
    $this->tickets->initialize();

    //акции
    $this->stocks = new reservation_stocks ();

    //спеццена
    $this->specprice = new reservation_specprice ();

    //paypal
    $this->pp = new reservation_pp ();

    //Скидки
    $this->discount = new reservation_discount ();

    //Наценки
    $this->extra = new ExtraEngine();

    //НДС
    $this->nds = new reservation_nds ();

    //Дверные коды
    $this->door_code = new reservation_doorcodes ();

    //Свет
    $this->light = new reservation_light ();
    $this->light->initialize();
    $this->webIo = new WebIoEngine();
    $this->webIo->initialize();

    $this->bar = new reservation_bar ();
    $this->bar->initialize();

    $this->pricing = new PricingReservationEngine();
  }

  function finalize()
  {
    $this->blocks->finalize();
    $this->clients->finalize();
    $this->holidays->finalize();
    $this->statistics->finalize();
    $this->tickets->finalize();
  }

  //изменить статус заказа в логе пайпал
  function setPayPalStatus($reservation_id, $state, $real_reservation_id = null)
  {
    $q = 'update ' . Query::tableName('reservations_tmp_paypal')
      . ' set pay_state = "' . $state . '"' .
      ($real_reservation_id && Service::query()::getDB()->checkField('real_reservation_id', 'reservations_tmp_paypal')
        ? ', real_reservation_id = "' . $real_reservation_id . '"' : '') .
      ' where reservation_id = "' . $reservation_id . '"';

    return Query::sqlQuery($q, [], false);
  }

  function setPaymentStateReservation($reservation_id)
  {
    $q = 'update ' . Query::tableName('reservations')
      . ' set payment_state = "1" where reservation_id = "'
      . (int)$reservation_id . '" ';

    return Query::sqlQuery($q, [], false);
  }

  function changeReservation($area_id, $mysql_datetime, $stock_id, $memo)
  {
    if ($stock_id === null || $this->stocks->checkStockExists($stock_id)) {
      $q = 'update ' . Query::tableName('reservations') . ' set stock_id = '
        . ($stock_id === null ? 'null' : $stock_id) . ', memo = "'
        . addslashes(
          $memo
        ) . '" where area_id = ' . $area_id . ' and start = "'
        . $mysql_datetime . '"';

      return Query::sqlQuery($q, [], false);
    } else {
      //акция не найдена
      return false;
    }
  }

  function changeReservationLightHeatingState(
    $area_id,
    $type,
    $mysql_datetime,
    $state,
    $price = false,
  ) {
    if ($type == 1 || $type == 2 || $type == 3) {
      switch ($type) {
        case 1:
          $name_state = 'light';
          break;
        case 2:
          $name_state = 'heating';
          break;
        case 3:
          $name_state = 'net';
          break;
      }
      $q = 'update ' . Query::tableName('reservations') . ' r set ' . ' r.'
        . $name_state . '_state = "' . $state . '"' . ($price ? ', r.'
          . $name_state . '_price = "' . $price . '"' : '')
        . ' where r.area_id = ' . $area_id . ' and r.start = "'
        . $mysql_datetime . '"';

      return Query::sqlQuery($q, [], false);
    }

    return false;
  }

  //данные о резервировани по заданной площадке и unixtime
  function getReservationData($area_id, $mysql_datetime, &$reservation_data)
  {
    $q    = 'select * from ' . Query::tableName('reservations') . '
			where area_id = ' . $area_id . ' and start = "' . $mysql_datetime
      . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //получаем строки таблицы
      $reservation_data = $temp[0];

      return true;
    } else {
      //запись о резервировании не найдена
      $reservation_data = null;

      return false;
    }
  }

  //данные о резервировани по заданному клиенту
  function getReservationDataByClient($client_id, ?int $encash, &$reservation_data = [])
  {
    $q                = 'SELECT r.*, rd.name, rd.value, rd.entry_id, rd.type_block,a.title as area_title,s.title as sport_title,t.title as type_title
					FROM ' . Query::tableName('reservations') . ' r
					LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON r.reservation_id = rd.reservation_id
                                        INNER JOIN ' . Query::tableName('areas') . ' a ON a.area_id = r.area_id
                                        INNER JOIN ' . Query::tableName('areas_types') . ' t ON t.type_id = a.type_id
                                        INNER JOIN ' . Query::tableName('areas_sports') . ' s ON s.sport_id = a.sport_id
					WHERE r.client_id = "' . $client_id . '"'
      . ($encash !== null ? ' AND r.encash = "' . $encash . '"' : '')
      . ' ORDER BY r.start DESC';
    $reservation_data = $this->renderReservationData(Query::sqlQuery($q));
    if (!empty($reservation_data)) {
      //получаем строки таблицы
      return true;
    }

    return false;
  }

  public function getRemovedReservationDataByClient($сlient_id, &$reservation_data)
  {
    $q
      = 'SELECT r.*, a.title AS area_title, t.title AS type_title  FROM ' . Query::tableName('reservations_log') . ' r
						LEFT JOIN ' . Query::tableName('areas') . ' a ON r.area_id = a.area_id
						LEFT JOIN ' . Query::tableName('areas_types') . ' t ON a.type_id = t.type_id
						WHERE r.client_id = "' . $сlient_id . '"
						ORDER BY r.start DESC';

    $reservation_data = $this->renderReservationData(Query::sqlQuery($q));
    if (!empty($reservation_data)) {
      //получаем строки таблицы
      return true;
    }

    return false;
  }

  //данные о резервировани для счета
  function getCountReservationByClient(
    $client_id,
    $date_start,
    $date_end,
    &$countReservation = 0,
    $date_finish = false,
    $type_id = false,
    $sport_id = false,
  ) {
    $q = 'SELECT COUNT(r.reservation_id) as count
      from ' . Query::tableName('reservations') . ' r
			inner join  ' . Query::tableName('areas') . ' a on r.area_id = a.area_id
			where client_id = "' . $client_id . '" and r.main_reservation_id is null' .
      ($type_id ? ' AND a.type_id = ' . $type_id . ' ' : '') .
      ($sport_id ? ' AND a.sport_id = ' . $sport_id . ' ' : '') .
      ' AND ((r.start >= "' . $date_start . '"' . ($date_end ? ' AND  r.start <= "' . $date_end . '"' : '') . ')' .
      ($date_finish ? ' OR (r.start<="' . $date_start . '" AND r.finish>="' . $date_start . '")' : '') . ')';
    if ($temp = Query::sqlQuery($q, [], true, ['onlyOne' => true])) {
      $countReservation = $temp['count'];

      //получаем строки таблицы
      return true;
    }

    //запись о резервировании не найдена

    return false;
  }

  //данные о резервировани по ее id
  function getReservationDataById(
    $id,
    &$reservation_data = [],
    $online_pay = false,
    $main = false,
  ) {
    $q    = 'select r.*, rd.name, rd.value, rd.entry_id, rd.type_block
      from ' . Query::tableName('reservations' . ($online_pay ? '_tmp_paypal' : '')) . ' r
			LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON r.reservation_id = rd.' . ($online_pay ? 'tmp_' : '') . 'reservation_id
			where r.' . ($main ? 'main_' : '') . 'reservation_id = ' . $id;
    $temp = $this->renderReservationData(Query::sqlQuery($q));
    if (!empty($temp) && isset($temp[$id])) {
      //получаем строки таблицы
      $reservation_data = $temp[$id];

      return true;
    }

    return false;
  }

  /* НАСТРОЙКИ СИСТЕМЫ */

  //максимальное время в unixtime для заказа
  function getMaxReservationUnixtime()
  {
    return $this->unixtime_max_orderable;
  }

  //задать настройки системы
  function setConfig(/* ... */)
  {
    //параметры функции
    $aliases2store = [
      'max_forward_reservation_days_count_close',
      'min_rejection_days_count_close',
      'max_forward_reservation_days_count_open',
      'min_rejection_days_count_open',
      'max_forward_reservation_days_count_mc_arena',
      'min_rejection_days_count_mc_arena',
      'admin_email',
      'notify_email',
      'email_subject_prefix',
      'order_notify',
      'count_door_code',
    ];
    foreach ($aliases2store as $index => $alias) {
      Query::sqlQuery(
        'update ' . Query::tableName('config') . '
				set value = "' . addslashes(func_get_arg($index)) . '"
				where alias = "' . $alias . '"',
        [],
        false
      );
      $this->config[$alias] = func_get_arg($index);
    }
  }

  function setDoorConfig(/* ... */)
  {
    //параметры функции
    $aliases2store = ['door_time_start', 'door_time_finish'];
    foreach ($aliases2store as $index => $alias) {
      Query::sqlQuery(
        'update ' . Query::tableName('config') . '
				set value = "' . addslashes(func_get_arg($index)) . '"
				where alias = "' . $alias . '"',
        [],
        false
      );
      $this->config[$alias] = func_get_arg($index);
    }
  }

  /* ПРОВЕРКИ */

  //заказан ли уже этот промежуток
  //true/false
  function checkAreaDateTimeOrdered(
    $area_id,
    $mysql_datetime_start,
    $mysql_datetime_finish = null,
    $client_id = null,
  ) {
    if ($mysql_datetime_finish == null) {
      $q = 'select count(*) as cnt from ' . Query::tableName('reservations')
        . ' where area_id = ' . $area_id . ' and start = "'
        . $mysql_datetime_start . '"' . (isset($client_id) ? (' and (client_id="' . ($client_id) . '" or main_client_id="' . ($client_id) . '")')
          : '');
    } else {
      $q = 'select count(*) as cnt from ' . Query::tableName('reservations')
        . ' where area_id = ' . $area_id . ' and start >= "'
        . $mysql_datetime_start . '" and finish<"'
        . $mysql_datetime_finish . '"' . (isset($client_id) ? (' and (client_id="' . ($client_id) . '" or main_client_id="' . ($client_id) . '")')
          : '');
    }
    $temp = Query::sqlQuery($q);

    return $temp[0]['cnt'] > 0;
  }

  /** установить максимальную дату бронирования
   *
   * @param int  $type_id
   * @param null $sport_id
   */
  public function setMaxReservationUnixTime($type_id = 1, $sport_id = 1)
  {
    $this->unixtime_max_orderable = strtotime(
      date('Y-m-d') . ' + ' . $this->config['min_max'][$type_id][$sport_id]['max_forward_reservation_days_count'] . ' days'
    );
  }


  //дата в unixtime находится в доступном для резервировании интервале
  function checkDateAvaliableByUnixtime($unixtime, $admin = false)
  {
    return $unixtime >= $this->unixtime_current_date
      && ($unixtime <= $this->unixtime_max_orderable || $admin);
  }

  //дата и время доступно для резервирования
  //полная проверка, учитывается все
  //коды возврата
  //true/false
  //коды в last_error
  //1 - время/день нерабочие
  //2 - время вне доступного диапазона
  //3 - праздник
  //4 - заблокирован
  //5 - уже заказан
  //6 - билет
  //7 - нет тарифа на период
  //mysql_date - yyyy-mm-dd
  //mysql_time - hh:mm
  function checkAreaDateTimeAvailable(
    $area_id,
    $client_id,
    $mysql_date,
    $mysql_time,
    &$error_code
  ): bool {
    $error_code     = 0;
    $mysql_datetime = $mysql_date . ' ' . $mysql_time;
    $unixtime       = strtotime($mysql_datetime);
    $is_admin       = Service::app()->isAdmin() && Service::auth()->checkAuth();
    $flag_all_time  = USE_ADMIN_ALL_TIME && $is_admin;
    $weekday        = CalendarHelper::getWeekdayByUnixtime($unixtime);
    $interval       = null;
    //доступность диапазона
    //площадка существует, рабочий день и рабочий промежуток площадки
    if (!$this->areas->checkWorktime(
      $area_id,
      HolidayHelper::resolveWeekday($mysql_date, HolidayScheduleType::Times),
      $unixtime,
      $interval
    )
    ) {
      $error_code = 1;

      return false;
    }

    if (!$this->pricing->hasPeriodPrice($area_id, $client_id, $mysql_date, $mysql_time, $error_code)) {
      return false;
    }

    $unixtime_max_orderable = mktime(
      '23',
      '59',
      '59',
      date('m', $this->unixtime_max_orderable),
      date('d', $this->unixtime_max_orderable),
      date('Y', $this->unixtime_max_orderable)
    );
    if ((!$flag_all_time)
      && (
        (
          USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME
          && $is_admin
          && !$this->checkAdminPossibilityOrderPastTime($mysql_date)
        )
        || (
          (
            (
              USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME
              && (!Service::app()->isAdmin()
                || !Service::auth()->checkAuth())

            )
            || !USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME
          )
          && (
            $unixtime < (time() - 60 * $interval)
            || $unixtime > $unixtime_max_orderable
          )
        ))
    ) {//допуск на час назад
      $error_code = 2;

      return false;
    }

    //праздник
    if ($this->holidays->checkHoliday($unixtime)) {
      $error_code = 3;

      return false;
    }

    //проверка времени на блокировку
    if ($this->blocks->checkAreaDateTimeBlocked(
      $area_id,
      $mysql_date,
      $mysql_time,
      $weekday
    )
    ) {
      $error_code = 4;

      return false;
    }

    //может промежуток уже заказан
    if ($this->checkAreaDateTimeOrdered(
      $area_id,
      $mysql_datetime
    )
    ) {
      $error_code = 5;

      return false;
    }

    if ($this->tickets->checkAreaDateTimeReserved(
      $area_id,
      $mysql_date,
      $mysql_time,
      $weekday
    )
    ) {
      $error_code = 6;

      return false;
    }

    return true;
  }

  /** получить все абонементы в текущий день
   *
   * @param $mysql_date
   * @param $type_id
   *
   * @return array
   */

  function getAbos($mysql_date, $type_id)
  {
    $tickets = [];
    // --- Защита от некорректных входных данных ---
    $mysql_date = trim($mysql_date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mysql_date) || !strtotime($mysql_date)) {
      return $tickets;
    }
    $type_id = (int)$type_id;

    if ($type_id < 1) {
      return $tickets;
    }

    $unixtime_date = strtotime($mysql_date);
    $weekday       = CalendarHelper::getWeekdayByUnixTime($unixtime_date);

    // Экранируем кавычки в дате (на всякий случай, даже при валидации)
    $mysql_date_escaped = addslashes($mysql_date);

    $tbl_tickets = Query::tableName('tickets');
    $tbl_periods = Query::tableName('tickets_periods');
    $tbl_areas   = Query::tableName('areas');
    $tbl_clients = Query::tableName('clients');

    $sql = '
        SELECT
            t.area_id,
            CONCAT(SUBSTRING(time_start, 1, 2), SUBSTRING(time_start, 4, 2)) AS time_start_hhmm,
            CONCAT(SUBSTRING(time_finish, 1, 2), SUBSTRING(time_finish, 4, 2)) AS time_finish_hhmm,
            c.client_id,
            c.name,
            c.surname,
            WEEK("' . $mysql_date_escaped . '", 3) AS week_current,
            COALESCE(t.week_begin, ms1.first_start_week) AS week_begin,
            t.ticket_id,
            tp.period_id,
            t.space,
            COALESCE(t.period_start, ms1.first_start) AS period_start
        FROM ' . $tbl_tickets . ' t
        INNER JOIN ' . $tbl_periods . ' tp
            ON t.ticket_id = tp.ticket_id
        INNER JOIN ' . $tbl_areas . ' a
            ON a.area_id = t.area_id
        INNER JOIN ' . $tbl_clients . ' c
            ON c.client_id = t.client_id
        LEFT JOIN (
            SELECT
                ticket_id,
                MIN(`start`) AS first_start,
                WEEK(MIN(`start`), 3) AS first_start_week
            FROM ' . $tbl_periods . '
            GROUP BY ticket_id
        ) ms1 ON ms1.ticket_id = t.ticket_id
        WHERE
            a.type_id = ' . $type_id . '
            AND tp.start <= "' . $mysql_date_escaped . '"
            AND tp.finish >= "' . $mysql_date_escaped . '"
            AND FIND_IN_SET(' . $weekday . ', t.weekdays)
    ';

    $temp = Query::sqlQuery($sql, [], true, ['style' => PDO::FETCH_NUM]);

    if (!empty($temp)) //формируем массив данных
    {
      foreach ($temp as $row) {
        $space      = $row[10];
        $start      = $row[11];
        $week       = $row[7];
        $first_game = CalendarHelper::getFirstDayTicket($start, $weekday, $week);
        if (CalendarHelper::checkDayForTicket($unixtime_date, $first_game[$weekday], $space)) {
          $tickets[$row[0]][] = [$row[1], $row[2], [$row[3], $row[4], $row[5]], $row[8], $row[9]];
        }
      }
    }

    return $tickets;
  }

  /* НОВЫЙ ИНТЕРФЕЙС ВЫДАЧИ */

  //возвращает массив по списком площадок заданного тип, с периодами и их статусами
  //формат элемента массива
  //out[n] = array (area_title, area_state_by_day, periods)
  //area_state_by_day - статус раб. дня, в виде битов (2) = [рабочий][целиком заблокирован]
  //periods - массив периодов
  //periods[time_start] = array (time_finish, period_state, additional_data)
  //period_state - статус периода, в виде битов (8) = [0 = прошел][1 = заблокирован][2 = одиночный заказ][3 = абонемент][4 = свет][5 = отопление][6 = сеть][7 = статус заказа]
  function getPeriodsByAreasTypeDate(
    $type_id,
    $limit,
    $mysql_date,
    &$out = [],
    &$error_code = 0,
    $sport_id = false,
    $area_page = null,
  )
  {
    static $outs;
    $use_area_page = USE_PAGE_BREAK_FROM_BASE_AREA && $area_page !== null;
    $key = $type_id . '|' . (int)$sport_id . '|' . $mysql_date . '|' . ($limit ? implode('_', $limit) : '')
      . '|page=' . ($use_area_page ? (int)$area_page : '');
    if (!empty($outs[$key])) {
      $out        = $outs[$key];
      $error_code = 0;

      return true;
    }
    //нет ли праздника в этот день?
    if ($this->holidays->checkHoliday($mysql_date) == false) {
      //дата в unixtime
      $unixtime_date = strtotime($mysql_date);
      //день недели
      $weekday = CalendarHelper::getWeekdayByUnixtime($unixtime_date);

      $timeRows       = $this->areas->getTimePeriodsByTypeId($type_id, $sport_id, $mysql_date);
      $disabled_times = Query::sqlQuery('SELECT * FROM ' . Query::tableName('tickets_disabled_time') . " WHERE disabled_date= '" . $mysql_date . "'");
//      Debug($disabled_times);


      $q    = 'select a.area_id, a.title, a.period, find_in_set(' . $weekday . ',a.workdays) > 0, a.light_on, a.heating_on , a.net_on, a.short_title ' .
        'from ' . Query::tableName('areas') . ' a where a.active = "1" and a.type_id = ' . $type_id . ($sport_id !== false
          ? ' and a.sport_id=' . $sport_id
          : '') . ($use_area_page ? ' and a.page=' . (int)$area_page : '') . ' order by a.sort' .
        (!$use_area_page && $limit !== null ? ' limit ' . (int)$limit[0] . ', ' . $limit[1] : '');
      $temp = Query::sqlQuery($q, [], true, ['style' => PDO::FETCH_NUM]);
      if (!empty($temp)) {
        //площадки есть
        $out = [];

        //пробиваем блокировки цельных периодов
        $blocks_normal = [];
        $q2            = 'select b.area_id, UNIX_TIMESTAMP(b.start) as start, UNIX_TIMESTAMP(b.finish) as finish, b.reason, b.use_webIo
						from ' . Query::tableName('blocks') . ' b,
							' . Query::tableName('areas') . ' a
						where
							a.type_id = ' . $type_id . ' and
							b.area_id = a.area_id and (
							DATE_FORMAT(b.start, "%Y-%m-%d") = "' . $mysql_date . '" or
							DATE_FORMAT(b.finish, "%Y-%m-%d") = "' . $mysql_date . '" or
							"' . $mysql_date . '" between b.start and b.finish)';
        //в виде массив вида (unixtime_старт, unixtime_финиш, комментарий к блокировке)
        $temp2 = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);
        foreach ($temp2 as $row) {
          $blocks_normal[$row[0]][] = [$row[1], $row[2], $row[3], 'use_webIo' => (int)$row[4]];
        }

        //пробиваем периодические блокировки
        $blocks_periodical = [];
        $q2                = 'select b.area_id,
							concat(substring(time_start,1,2),substring(time_start,4,2)),
							concat(substring(time_finish,1,2),substring(time_finish,4,2)),
							b.reason, b.use_webIo
						from ' . Query::tableName('blocks_periodical') . ' b,
							' . Query::tableName('areas') . ' a
						where
							a.type_id = ' . $type_id . ' and
							b.area_id = a.area_id and
							weekday_' . $weekday . ' = 1 and 
							b.unlimited_block = 0 and 
							"' . $mysql_date . '" between date_start and date_finish';
        $temp2             = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);
        foreach ($temp2 as $row) {
          $blocks_periodical[$row[0]][] = [$row[1], $row[2], $row[3], 'use_webIo' => (int)$row[4]];
        }

        //пробиваем бесконечные блокировки
        $blocks_unlimited = [];
        $q2               = 'select b.area_id,
                            concat(substring(time_start,1,2),substring(time_start,4,2)),
                            concat(substring(time_finish,1,2),substring(time_finish,4,2)),
                            b.reason, b.use_webIo
                        from ' . Query::tableName('blocks_periodical') . ' b,
                            ' . Query::tableName('areas') . ' a
                        where
                            a.type_id = ' . $type_id . ' and
                            b.area_id = a.area_id and
                            weekday_' . $weekday . ' = 1 and 
                            b.unlimited_block = 1 and 
                            "' . $mysql_date . '" >= date_start';
        $temp2            = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);
        foreach ($temp2 as $row) {
          $blocks_unlimited[$row[0]][] = [$row[1], $row[2], $row[3], 'use_webIo' => (int)$row[4]];
        }

        $tickets = $this->getAbos($mysql_date, $type_id);

        //пробиваем свет
        $tmp_data     = AreasLightsModel::getAreasDefaultStateData();
        $light_data   = isset($tmp_data[1]) ? $tmp_data[1] : [];
        $heating_data = isset($tmp_data[2]) ? $tmp_data[2] : [];
        $net_data     = isset($tmp_data[3]) ? $tmp_data[3] : [];
        unset($tmp_data);

        if ($light_data_ticket = $this->tickets->getLightTicketData(date('Y-m-d', $unixtime_date))) {
          if (isset($light_data_ticket[1])) {
            foreach ($light_data_ticket[1] as $area_id => $time_data) {
              foreach ($time_data as $t_weekday => $times_start) {
                foreach ($times_start as $time_start) {
                  $light_data[$area_id][$t_weekday][] = $time_start;
                }
              }
            }
          }
          if (isset($light_data_ticket[2])) {
            foreach ($light_data_ticket[2] as $area_id => $time_data) {
              foreach ($time_data as $t_weekday => $times_start) {
                foreach ($times_start as $time_start) {
                  $heating_data[$area_id][$t_weekday][] = $time_start;
                }
              }
            }
          }
          if (isset($light_data_ticket[3])) {
            foreach ($light_data_ticket[3] as $area_id => $time_data) {
              foreach ($time_data as $t_weekday => $times_start) {
                foreach ($times_start as $time_start) {
                  $net_data[$area_id][$t_weekday][] = $time_start;
                }
              }
            }
          }
        }

        $areas_ids = '';
        //перебираем площадки
        foreach ($temp as $area_data) {
          //название площадки
          $out[$area_data[0]][0] = $area_data[1];


          //статус площадки
          $out[$area_data[0]][1]                    = $area_data[3] . '0';//проверка наличия блокировки на весь день пока нет :( х3 как сделать
          $out[$area_data[0]][4]                    = $area_data[4];      //проверка наличия системы света для этой площади
          $out[$area_data[0]][5]                    = $area_data[5];      //проверка наличия системы отопления для этой площади
          $out[$area_data[0]][6]                    = $area_data[6];      //проверка наличия системы поднятия сети для этой площади
          $out[$area_data[0]]['short_title']        = $area_data[7];
          $out[$area_data[0]]['online_reservation'] = isset($area_data[8]) ? $area_data[8] : 1;// можно бронировать онлайн

          if ($area_data[3]) {
            //площадка работает в этот день
            $areas_ids .= $area_data[0] . ',';

            //проходимся по периодам площадки
            foreach ($timeRows[$area_data[0]][$weekday] as $period) {
              //перебираем периоды
              $out[$area_data[0]][2][$period[0]] = [$period[1], $period[2] . '000000'];

              //время в циферках - 08:00 = 800, 24:00 = 2400
              $time = substr($period[0], 0, 2) . substr($period[0], 3, 2);

              //начало периода в unixtime
              $unixtime2check = strtotime($mysql_date . ' ' . $period[0]);

              //проверяем на блокировки begin
              //обычные блокировки
              if (isset ($blocks_normal[$area_data[0]])) {
                foreach ($blocks_normal[$area_data[0]] as $block_data) {
                  if ($block_data[0] <= $unixtime2check && $block_data[1] > $unixtime2check) {
                    $out[$area_data[0]][2][$period[0]][1][1]            = 1;
                    $out[$area_data[0]][2][$period[0]][2]['block']      = $block_data[2];
                    $out[$area_data[0]][2][$period[0]][2]['block_data'] = [
                      'title'     => $block_data[2],
                      'use_webIo' => $block_data['use_webIo'],
                    ];
                  }
                }
              }
              //периодические блокировки (проверять только в случае, если обычной блокировки на период нет)
              if ($out[$area_data[0]][2][$period[0]][1][1] == 0 && isset ($blocks_periodical[$area_data[0]])) {
                foreach ($blocks_periodical[$area_data[0]] as $block_data) {
                  if ($block_data[0] <= $time && $block_data[1] > $time) {
                    $out[$area_data[0]][2][$period[0]][1][1]            = 1;
                    $out[$area_data[0]][2][$period[0]][2]['block']      = $block_data[2];
                    $out[$area_data[0]][2][$period[0]][2]['block_data'] = [
                      'title'     => $block_data[2],
                      'use_webIo' => $block_data['use_webIo'],
                    ];
                  }
                }
              }
              //проверяем на блокировку end

              //бесконечные блокировки
              if ($out[$area_data[0]][2][$period[0]][1][1] == 0 && isset ($blocks_unlimited[$area_data[0]])) {
                foreach ($blocks_unlimited[$area_data[0]] as $block_data) {
                  if ($block_data[0] <= $time && $block_data[1] > $time) {
                    $out[$area_data[0]][2][$period[0]][1][1]            = 1;
                    $out[$area_data[0]][2][$period[0]][2]['block']      = $block_data[2];
                    $out[$area_data[0]][2][$period[0]][2]['block_data'] = [
                      'title'     => $block_data[2],
                      'use_webIo' => $block_data['use_webIo'],
                    ];
                  }
                }
              }
              //бесконечные блокировки end

              //проверяем абонементы begin
              if (isset ($tickets[$area_data[0]])) {
                //берем всё деактивированное время за заданный день


                foreach ($tickets[$area_data[0]] as $ticket_data) {
                  //"собираем" текущее время из числа
                  $checked_time = "" . $time[0] . $time[1] . ":" . $time[2] . $time[3] . ":00";
                  //проверяем есть ли текущее время в неиспользуемых временных промежутках
                  //$dis_time_query = "SELECT * FROM " . DB_TABLE_PREFIX . "tickets_disabled_time WHERE ticket_id= ". $ticket_data[3] ." AND period_id= " . $ticket_data[4] . " AND disabled_time= '". $checked_time . "' AND disabled_date= '" . $mysql_date . "'";
                  $is_time_disabled = false;
                  foreach ($disabled_times as $disabled_time_item) {
                    if ($disabled_time_item["ticket_id"] == $ticket_data[3] &&
                      $disabled_time_item["disabled_time"] == $checked_time) {
                      $is_time_disabled = true;
                    }
                  }

                  if (!$is_time_disabled) {
                    if ($ticket_data[0] <= $time and $ticket_data[1] > $time) {
                      //рассматриваемый период занят т.к. на это время есть абонемент
                      $out[$area_data[0]][2][$period[0]][1][3]           = 1;
                      $out[$area_data[0]][2][$period[0]][2]['ticket_id'] = $ticket_data[3];
                      $out[$area_data[0]][2][$period[0]][2]['client']    = $ticket_data[2];
                    }
                  }
                }
              }
              //проверяем абонементы end
              //проверяем абонементы свет begin
              if (isset($light_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                if (in_array($period[0], $light_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                  $out[$area_data[0]][2][$period[0]][1][4] = 1;
                }
              }
              //проверяем абонементы свет end
              //проверяем абонементы отопление begin
              if (isset($heating_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                if (in_array($period[0], $heating_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                  $out[$area_data[0]][2][$period[0]][1][5] = 1;
                }
              }
              //проверяем абонементы отопление end
              //проверяем абонементы на сеть begin
              if (isset($net_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                if (in_array($period[0], $net_data[$area_data[0]][CalendarHelper::getWeekdayByUnixtime($unixtime_date)])) {
                  $out[$area_data[0]][2][$period[0]][1][6] = 1;
                }
              }
              //проверяем абонементы на сеть end
            }
          }
        }

        if ($areas_ids != '') {
          //есть задействоваванные площадки

          foreach ($this->getTmpReservation($mysql_date, $areas_ids) as $item) {
            $out[$item['area_id']][2][$item['time_start']] = [$item['time_finish'], '1000000', 'tmp' => 1];
          }
          /*
          //по уму бы надо сюда вписать все проверки - на абонементы, блокировки и т.п. - чтобы по 356 раз не проверять... это подумать еще надо
          //проставялем заказы begin
          $q = 'select r.area_id, substring(r.start,12,5), c.client_id,' .
            'if(r.client_id is null,r.client_name,c.name), if(r.client_id is null,r.client_surname,c.surname),' .
            'r.customer, r.memo, r.stock_id, r.light_state, r.heating_state, r.net_state,  r.status,  r.reservation_id,
             r.main_reservation_id, r.main_client_id, c.club_state, c.area_type, r.price, r.encash, a.period, c.abo_delete ' .
            ', r.type_reservation, r.street_friends,
             (select count(reservation_id) from ' . Query::tableName('reservations') . ' where r.main_reservation_id is null 
             and (main_reservation_id = r.reservation_id or r.reservation_id = reservation_id) )
             , rd.name, rd.value, rd.entry_id, rd.type_block ' .
            'from ' . Query::tableName('reservations') . ' r ' .
            'left join ' . Query::tableName('clients') . ' c on c.client_id = r.client_id ' .
            'LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON r.reservation_id = rd.reservation_id ' .
            'left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id ' .
            'where ' .
            'r.area_id in (' . substr($areas_ids, 0, -1) . ') and ' .
            'r.start between "' . $mysql_date . ' 00:00:00" and "' . $mysql_date . ' 23:59:59" order by r.start';
          // todo: переделать на выборку не по номерам полей
          $temp2 = [];

          foreach (Query::sqlQuery($q, [], true, ['style' => PDO::FETCH_NUM]) as $item) {
            if(!isset($temp2[$item[12]])){
              $temp2[$item[12]] = $item;
              unset($temp2[$item[12]][24], $temp2[$item[12]][25],$temp2[$item[12]][26],$temp2[$item[12]][27]);
            }
            if($item[24] && $item[26] && $item[27]){
              $temp2[$item[12]][$item['27']][$item['26']][$item[24]] = $item[25];
            }
          }
           */
          // Экранируем ID зон — предполагаем, что это целые числа
          $areasIdsSafe = substr($areas_ids, 0, -1);

          // Экранируем дату — допустим, $mysql_date — строка вида '2025-12-24'
          // Защищаем от инъекции через простую валидацию формата

          $nextDay = date('Y-m-d', strtotime($mysql_date . ' +1 day'));

          $chk_customer_title = Service::query()::getDB()->checkField('customer_title', 'reservations');

          // Формируем запрос как строку (готов к выполнению через mysql_query / $DB->Query и т.п.)
          $q_loc = '
            SELECT
                r.area_id,
                DATE_FORMAT(r.start, \'%H:%i\') AS start_time,
                c.client_id,
                COALESCE(c.name, r.client_name) AS client_name,
                COALESCE(c.surname, r.client_surname) AS client_surname,
                r.customer,
                r.memo,
                r.stock_id,
                r.light_state,
                r.heating_state,
                r.net_state,
                r.status,
                r.reservation_id,
                r.main_reservation_id,
                r.main_client_id,
                c.club_state,
                c.area_type,
                r.price,
                r.encash,
                a.period,
                c.abo_delete,
                r.type_reservation,
                r.street_friends,
                COALESCE(main_count.cnt, 0) AS linked_reservations_count,
                rd.name,
                rd.value,
                rd.entry_id,
                rd.type_block,
                r.payment_state' .
            (($chk_customer_title) ? ", r.customer_title" : "") . '
            FROM ' . Query::tableName('reservations') . ' r
            LEFT JOIN ' . Query::tableName('clients') . ' c 
                ON c.client_id = r.client_id
            LEFT JOIN ' . Query::tableName('areas') . ' a 
                ON a.area_id = r.area_id
            LEFT JOIN ' . Query::tableName('reservation_data') . ' rd 
                ON rd.reservation_id = r.reservation_id
            LEFT JOIN (
                SELECT 
                    COALESCE(main_reservation_id, reservation_id) AS group_id,
                    COUNT(*) AS cnt
                FROM ' . Query::tableName('reservations') . '
                WHERE main_reservation_id IS NULL
                GROUP BY group_id
            ) main_count 
                ON main_count.group_id = r.reservation_id
            WHERE 
                r.area_id IN (' . $areasIdsSafe . ')
                AND r.start >= "' . $mysql_date . ' 00:00:00"
                AND r.start < "' . $nextDay . ' 00:00:00"
            ORDER BY r.start
            ';
          $temp  = [];

          foreach (Query::sqlQuery($q_loc, [], true, ['style' => PDO::FETCH_NUM]) as $item) {
            if (!isset($temp[$item[12]])) {
              $temp[$item[12]] = $item;
              unset($temp[$item[12]][24], $temp[$item[12]][25], $temp[$item[12]][26], $temp[$item[12]][27]);
            }
            if ($item[24] && $item[26] && $item[27]) {
              $temp[$item[12]][$item[27]][$item[26]][$item[24]] = $item[25];
            }
          }

          $client_id_before = $time_start_before = $sum_price = $area_id_before = [];
          foreach ($temp as $row) {
            $socksCode = $this->getStocksCodeByIds(isset($row['stocks']) ? array_keys($row['stocks']) : [$row[7]]);
            $id        = $row[2];
            if ($id == null) {
              $id = $row[3] . ' ' . $row[4];
            }
            if (!isset($client_id_before[$row[0]]) || $client_id_before[$row[0]] != $id) {
              $sum_price[$row[0]]        = $row[17];
              $area_id_before[$row[0]]   = $row[0];
              $client_id_before[$row[0]] = $id;
            } else {
              $timedifference = strtotime(date('Y-m-d ' . $row[1])) - strtotime(
                  date('Y-m-d ' . $time_start_before[$row[0]])
                );
              if ((function_exists('bcdiv') && (bcdiv($timedifference, 60) == $row[19]))
                || (!function_exists(
                    'bcdiv'
                  ) && $timedifference % 60 == 0 && ((int)$timedifference / 60) == $row[19])) {
                $sum_price[$row[0]]                                                        += $row[17];
                $out[$area_id_before[$row[0]]][2][$time_start_before[$row[0]]][2]['price'] = '&#9660;';
              } else {
                $sum_price[$row[0]] = $row[17];
              }
            }

            //проставляем сделанные заказы заказы
            $out[$row[0]][2][$row[1]][1][2]                     = 1;
            $out[$row[0]][2][$row[1]][1][4]                     = $row[8];  //установить бит статуса света
            $out[$row[0]][2][$row[1]][1][5]                     = $row[9];  //установить бит статуса отопления
            $out[$row[0]][2][$row[1]][1][6]                     = $row[10]; //установить бит статуса на сеть
            $out[$row[0]][2][$row[1]][1][7]                     = $row[11]; //установить бит статуса заказа (0 - выполнен не до конца, 1 - выполнен)
            $out[$row[0]][2][$row[1]][2]['client']              = [
              $row[2],
              $row[3],
              $row[4],
              'client_id'      => $row[2],
              'client_name'    => $row[3],
              'client_surname' => $row[4],
              'area_type'      => $row[16],
              'abo_delete'     => $row[20],
            ];
            $out[$row[0]][2][$row[1]][2]['customer']            = $row[5];
            $out[$row[0]][2][$row[1]][2]['memo']                = $row[6];
            $out[$row[0]][2][$row[1]][2]['stock']               = !empty($socksCode) ? implode(' | ', $socksCode) : '';
            $out[$row[0]][2][$row[1]][2]['encash']              = $row[18];
            $out[$row[0]][2][$row[1]][2]['price']               = number_format(
                $sum_price[$row[0]],
                2,
                ',',
                ''
              ) . ' ' . CURR_VALUTE;
            $out[$row[0]][2][$row[1]][2]['reservation_id']      = $row[12];
            $out[$row[0]][2][$row[1]][2]['main_reservation_id'] = $row[13];
            $out[$row[0]][2][$row[1]][2]['main_client_id']      = $row[14];
            $out[$row[0]][2][$row[1]][2]['club_state']          = $row[15];
            $out[$row[0]][2][$row[1]][2]['payment_state']       = ($row[28] > 0 ? 1 : 0);
            $time_start_before[$row[0]]                         = $row[1];
            $out[$row[0]][2][$row[1]][2]['type_reservation']    = $row[21];
            $out[$row[0]][2][$row[1]][2]['street_friends']      = $row[22] ? unserialize($row[22], ['allowed_classes' => false]) : [];
            $out[$row[0]][2][$row[1]][2]['numberOfPeriods']     = $row[23] ?: 0;
            $out[$row[0]][2][$row[1]][2]['customer_title']      = $chk_customer_title ? explode('|', $row[29]) : null;
          }
          //проставялем заказы end
        }
        //ошибки нет
        $error_code = 0;
      } else {
        //площадки не найдены
        $error_code = 2;
        $out        = [];
      }
    } else {
      //день - праздник, без мазы!
      $error_code
        = 1;
      $out
        = [];
    }
    $outs[$key] = $out;

    return $error_code == 0;
  }


//возвращает массив по списком площадок заданного тип, с периодами и их статусами
//формат элемента массива
//out[n] = array (area_title, area_state_by_day, periods)
//area_state_by_day - статус раб. дня, в виде битов (2) = [рабочий][целиком заблокирован]
//periods - массив периодов
//periods[time_start] = array (time_finish, period_state, additional_data)
//period_state - статус периода, в виде битов (5) = [прошел][заблокирован][одиночный заказ][абонемент][свет][отопление][сеть]
  function getPeriodsByAreasTypeDateWeek(
    $type_id,
    $limit,
    $mysql_date,
    &$out,
    &$weekdays_title,
    &$error_code,
    $sport_id = false,
    $area_page = null,
  ) {
    $use_area_page = USE_PAGE_BREAK_FROM_BASE_AREA && $area_page !== null;
    //нет ли праздника в этот день?
    if ($this->holidays->checkHoliday($mysql_date) == false) {
      //дата в unixtime
      $unixtime_date = strtotime($mysql_date);

      $finish_date = mktime(
        date('H', $unixtime_date),
        date('i', $unixtime_date),
        date('s', $unixtime_date),
        date('m', $unixtime_date),
        ((int)date('d', $unixtime_date) + 7),
        date('Y', $unixtime_date)
      );
      $out         = [];
      while ($unixtime_date < $finish_date) {
        $weekday    = CalendarHelper::getWeekdayByUnixtime($unixtime_date);
        $mysql_date = date('Y-m-d', $unixtime_date);
        $timeRows   = $this->areas->getTimePeriodsByTypeId($type_id, $sport_id, $mysql_date, date('Y-m-d', $finish_date));

        $weekdays_title[$weekday] = (object)['title' => TranslateHelper::translateWeekday($weekday), 'date' => date('d.m.Y', $unixtime_date)];

        $unixtime_date = mktime(
          date('H', $unixtime_date),
          date('i', $unixtime_date),
          date('s', $unixtime_date),
          date('m', $unixtime_date),
          ((int)date('d', $unixtime_date) + 1),
          date('Y', $unixtime_date)
        );

        $q    = 'select a.area_id, a.title, a.period, find_in_set(' . $weekday . ',a.workdays) > 0, a.light_on, a.heating_on , a.net_on, a.short_title ' .
          'from ' . Query::tableName('areas') . ' a where a.active = "1" and a.type_id = ' . $type_id . ($sport_id ? ' and a.sport_id=' . $sport_id
            : '') . ($use_area_page ? ' and a.page=' . (int)$area_page : '') . ' order by a.sort' .
          (!$use_area_page && $limit !== null ? ' limit ' . (int)$limit[0] . ',' . $limit[1] : '');
        $temp = Query::sqlQuery($q, [], true, ['style' => PDO::FETCH_NUM]);
        if (!empty($temp)) {
          //площадки есть
          //пробиваем блокировки цельных периодов
          $blocks_normal = [];
          $q2            = 'select b.area_id, UNIX_TIMESTAMP(b.start) as start, UNIX_TIMESTAMP(b.finish) as finish, b.reason
                                                    from ' . Query::tableName('blocks') . ' b,
                                                            ' . Query::tableName('areas') . ' a
                                                    where
                                                            a.type_id = ' . $type_id . ' and
                                                            b.area_id = a.area_id and (
                                                            DATE_FORMAT(b.start, "%Y-%m-%d") = "' . $mysql_date . '" 
                                                            OR DATE_FORMAT(b.finish, "%Y-%m-%d") = "' . $mysql_date . '" 
                                                            OR "' . $mysql_date . '" BETWEEN b.start 
                                                            AND b.finish)';

          //в виде массив вида (unixtime_старт, unixtime_финиш, комментарий к блокировке)
          $temp2 = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);
          foreach ($temp2 as $row) {
            $blocks_normal[$row[0]][] = [$row[1], $row[2], $row[3]];
          }


          //пробиваем периодические блокировки
          $blocks_periodical = [];
          $q2                = 'select b.area_id,
                                                            concat(substring(time_start,1,2),substring(time_start,4,2)),
                                                            concat(substring(time_finish,1,2),substring(time_finish,4,2)),
                                                            b.reason
                                                    from ' . Query::tableName('blocks_periodical') . ' b,
                                                            ' . Query::tableName('areas') . ' a
                                                    where
                                                            a.type_id = ' . $type_id . ' and
                                                            b.area_id = a.area_id and
                                                            weekday_' . $weekday . ' = 1 and
                                                            "' . $mysql_date . '" between date_start and date_finish';

          $temp2 = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);

          foreach ($temp2 as $row) {
            $blocks_periodical[$row[0]][] = [$row[1], $row[2], $row[3]];
          }

          //пробиваем бесконечные блокировки
          $blocks_unlimited = [];
          $q2               = 'select b.area_id,
                            concat(substring(time_start,1,2),substring(time_start,4,2)),
                            concat(substring(time_finish,1,2),substring(time_finish,4,2)),
                            b.reason
                        from ' . Query::tableName('blocks_periodical') . ' b,
                            ' . Query::tableName('areas') . ' a
                        where
                            a.type_id = ' . $type_id . ' and
                            b.area_id = a.area_id and
                            weekday_' . $weekday . ' = 1 and 
                            b.unlimited_block = 1 and 
                            "' . $mysql_date . '" >= date_start';

          $temp2 = Query::sqlQuery($q2, [], true, ['style' => PDO::FETCH_NUM]);

          foreach ($temp2 as $row) {
            $blocks_unlimited[$row[0]][] = [$row[1], $row[2], $row[3]];
          }

          $tickets = $this->getAbos($mysql_date, $type_id);
          $disabled_times = [];
          foreach (Query::sqlQuery(
            'SELECT ticket_id, disabled_time FROM ' . Query::tableName('tickets_disabled_time')
            . ' WHERE disabled_date = :disabled_date',
            [':disabled_date' => $mysql_date]
          ) as $disabledTime) {
            $disabled_times[$disabledTime['ticket_id']][$disabledTime['disabled_time']] = true;
          }

          $areas_ids = '';
          //перебираем площадки
          foreach ($temp as $area_data) {
            //статус площадки
            //$out[$area_data[0]][1] = $area_data[3] . '0';//проверка наличия блокировки на весь день пока нет :( х3 как сделать


            if ($area_data[3]) {
              //площадка работает в этот день
              $areas_ids .= $area_data[0] . ',';

              //проходимся по периодам площадки
              foreach ($timeRows[$area_data[0]][$weekday] as $period) {
                //перебираем периоды
                $out[$period[0]][0]                                                = $period[1];
                $out[$period[0]][1][$weekday][$area_data[0]]['weekday_date']       = $mysql_date;
                $one                                                               = trim($area_data[1], '"');
                $out[$period[0]][1][$weekday][$area_data[0]][0]                    = $area_data[7]
                  ? $area_data[7]
                  : strtolower($one[0]);
                $out[$period[0]][1][$weekday][$area_data[0]][1]                    = $period[2] . '000000';
                $out[$period[0]][1][$weekday][$area_data[0]]['online_reservation'] = isset($area_data[8]) ? $area_data[8] : 1;

                //время в циферках - 08:00 = 800, 24:00 = 2400
                $time = substr($period[0], 0, 2) . substr($period[0], 3, 2);

                //начало периода в unixtime
                $unixtime2check = strtotime($mysql_date . ' ' . $period[0]);

                //проверяем на блокировки begin
                //обычные блокировки
                if (isset ($blocks_normal[$area_data[0]])) {
                  foreach ($blocks_normal[$area_data[0]] as $block_data) {
                    if ($block_data[0] <= $unixtime2check && $block_data[1] > $unixtime2check) {
                      $out[$period[0]][1][$weekday][$area_data[0]][1][1]       = 1;
                      $out[$period[0]][1][$weekday][$area_data[0]][2]['block'] = $block_data[2];
                    }
                  }
                }
                //периодические блокировки (проверять только в случае, если обычной блокировки на период нет)
                if ($out[$period[0]][1][$weekday][$area_data[0]][1][1] == 0 && isset ($blocks_periodical[$area_data[0]])) {
                  foreach ($blocks_periodical[$area_data[0]] as $block_data) {
                    if ($block_data[0] <= $time && $block_data[1] > $time) {
                      $out[$period[0]][1][$weekday][$area_data[0]][1][1]       = 1;
                      $out[$period[0]][1][$weekday][$area_data[0]][2]['block'] = $block_data[2];
                    }
                  }
                }
                //бесконечные блокировки
                if ($out[$period[0]][1][$weekday][$area_data[0]][1][1] == 0 && isset ($blocks_unlimited[$area_data[0]])) {
                  foreach ($blocks_unlimited[$area_data[0]] as $block_data) {
                    if ($block_data[0] <= $time && $block_data[1] > $time) {
                      $out[$period[0]][1][$weekday][$area_data[0]][1][1]       = 1;
                      $out[$period[0]][1][$weekday][$area_data[0]][2]['block'] = $block_data[2];
                    }
                  }
                }
                //бесконечные блокировки end
                //проверяем на блокировку end

                //проверяем абонементы begin
                if (isset ($tickets[$area_data[0]])) {
                  foreach ($tickets[$area_data[0]] as $ticket_data) {
                    //"собираем" текущее время из числа
                    $checked_time = "" . $time[0] . $time[1] . ":" . $time[2] . $time[3] . ":00";
                    //проверяем есть ли текущее время в неиспользуемых временных промежутках
                    $is_time_disabled = isset($disabled_times[$ticket_data[3]][$checked_time]);

                    if (!$is_time_disabled) {
                      if ($ticket_data[0] <= $time and $ticket_data[1] > $time) {
                        //рассматриваемый период занят т.к. на это время есть абонемент
                        $out[$period[0]][1][$weekday][$area_data[0]][1][3]           = 1;
                        $out[$period[0]][1][$weekday][$area_data[0]][3]['ticket_id'] = $ticket_data[3];
                        $out[$period[0]][1][$weekday][$area_data[0]][3]['client']    = $ticket_data[2];
                      }
                    }
                  }
                }
                //проверяем абонементы end
              }
            }
          }
          if ($areas_ids != '') {
            //есть задействоваванные площадки

            foreach ($this->getTmpReservation($mysql_date, $areas_ids) as $item) {
              $out[$item['time_start']][1][$weekday][$item['area_id']][1]     = '1000000';
              $out[$item['time_start']][1][$weekday][$item['area_id']]['tmp'] = 1;
            }
            // todo - здесь не дорабатывал stocks  на возможность выбора несколько опций сразу - вроде это ни где не отображается
            //по уму бы надо сюда вписать все проверки - на абонементы, блокировки и т.п. - чтобы по 356 раз не проверять... это подумать еще надо
            //проставялем заказы begin
            $q    = 'select r.area_id, substring(r.start,12,5), c.client_id,' .
              'if(r.client_id is null,r.client_name,c.name), if(r.client_id is null,r.client_surname,c.surname),' .
              'r.customer, r.memo, rs.code, r.light_state, r.heating_state, r.net_state, r.status,  r.reservation_id, r.main_reservation_id, r.main_client_id, c.club_state, c.area_type ' .
              'from ' . Query::tableName('reservations') . ' r ' .
              'left join ' . Query::tableName('clients') . ' c on c.client_id = r.client_id ' .
              'left join ' . Query::tableName('reservations_stocks') . ' rs on rs.stock_id = r.stock_id ' .
              'where ' .
              'r.area_id in (' . substr($areas_ids, 0, -1) . ') and ' .
              'r.start between "' . $mysql_date . ' 00:00:00" and "' . $mysql_date . ' 23:59:59"';
            $temp = Query::sqlQuery($q, [], true, ['style' => PDO::FETCH_NUM]);
            foreach ($temp as $row) {
              //проставляем сделанные заказы заказы
              $out[$row[1]][1][$weekday][$row[0]][1][2]          = 1;
              $out[$row[1]][1][$weekday][$row[0]][3]['client']   = [
                $row[2],
                $row[3],
                $row[4],
                'client_id'      => $row[2],
                'client_name'    => $row[3],
                'client_surname' => $row[4],
                'area_type'      => $row[16],
              ];
              $out[$row[1]][1][$weekday][$row[0]][3]['customer'] = $row[5];
              $out[$row[1]][1][$weekday][$row[0]][3]['memo']     = $row[6];
              $out[$row[1]][1][$weekday][$row[0]][3]['stock']    = $row[7];

              $out[$row[1]][1][$weekday][$row[0]][3]['status']              = $row[11];
              $out[$row[1]][1][$weekday][$row[0]][3]['reservation_id']      = $row[12];
              $out[$row[1]][1][$weekday][$row[0]][3]['main_reservation_id'] = $row[13];
              $out[$row[1]][1][$weekday][$row[0]][3]['main_client_id']      = $row[14];
              $out[$row[1]][1][$weekday][$row[0]][3]['club_state']          = $row[15];
            }
            //проставялем заказы end
          }
          ksort($out);
          //ошибки нет
          $error_code = 0;
        } else {
          //площадки не найдены
          $error_code = 2;
          $out        = null;
        }
      }
    } else {
      //день - праздник, без мазы!
      $error_code = 1;
      $out        = null;
    }

    return $error_code == 0;
  }

  public function getLastAndNextReservationByClientBeforeDate(
    $client_id,
    $date,
  ) {
    $reservations = [
      'last' => [],
      'next' => [],
    ];

    $date = date('Y-m-d 23:59:59', strtotime($date));

    $temp = Query::sqlQuery(
      'select * from ' . Query::tableName('reservations') . ' 
        where client_id = "' . $client_id . '" and start <= "' . $date
      . '" order by start desc limit 1'
    );

    if (!empty($temp)) {
      $reservations['last'] = $temp[0];

      $q     = 'select * from ' . Query::tableName('reservations')
        . ' where client_id = "' . $client_id
        . '" and date_format(start, "%Y-%m-%d") = "' . date(
          'Y-m-d',
          strtotime($reservations['last']['start'])
        ) . '"';
      $temp2 = Query::sqlQuery($q);
      $sum   = 0;
      if (!empty($temp2)) {
        foreach ($temp2 as $row) {
          $sum += $row['price'] + $row['light_price']
            + $row['heating_price'];
        }
      }

      $reservations['last']['sum_price'] = $sum;
    }

    $q     = 'select * from ' . Query::tableName('reservations') . ' 
        where client_id = "' . $client_id . '" and start > "' . $date
      . '" order by start limit 1';
    $temp2 = Query::sqlQuery($q);
    if (!empty($temp2)) {
      $reservations['next'] = $temp2[0];
    }

    return $reservations;
  }

  public function getMinReservationDate($type_id, $sport_id)
  {
    $tmp = Query::sqlQuery(
      'select min(r.start) as min from ' . Query::tableName('reservations') . ' r'
      . ' left join ' . Query::tableName('areas') . ' a on r.area_id = a.area_id'
      . ' where a.type_id = "' . $type_id . '" and a.sport_id ="' . $sport_id . '"'
      , [], true, ['onlyOne' => true])['min'];

    return date('Y-m', strtotime($tmp ?: 'now'));
  }

  /**
   *  Проверяется возможность бронирования для админа прошедшего времени
   *
   * @param          $date
   * @param bool|int $month
   *
   * @return bool
   */
  public function checkAdminPossibilityOrderPastTime($date, $month = false)
  {
    if (!$month) {
      $month = ORDER_PAST_TIME;
    }

    $date_old = strtotime(
      date(
        'Y-m-01',
        strtotime('now - ' . $month . ' month')
      )
    );
    if (USE_ADMIN_POSSIBILITY_ORDER_PAST_TIME
      && strtotime($date) >= $date_old
    ) {
      return true;
    }

    return false;
  }

  //данные о резервированиях по ее id
  function getReservationDataByIds($ids, &$reservation_data, $online_pay = false)
  {
    $q                = 'SELECT atp.title as type_title, atp.type_id, att.sport_id, att.title as sport_title, a.title as area_title, a.period, r.* FROM ' . Query::tableName(
        'reservations' . ($online_pay ? '_tmp_paypal' : '')
      ) . ' r 
              LEFT JOIN ' . Query::tableName('areas') . ' a ON a.area_id = r.area_id 
              RIGHT JOIN ' . Query::tableName('areas_sports') . ' att ON att.sport_id = a.sport_id 
              RIGHT JOIN ' . Query::tableName('areas_types') . ' atp ON atp.type_id = a.type_id 
              WHERE r.reservation_id IN (' . join(',', $ids) . ')';
    $reservation_data = Query::sqlQuery($q);
    if (!empty($reservation_data)) {

      return true;
    } else {
      //запись о резервировании не найдена
      $reservation_data = null;

      return false;
    }
  }

  public function obtainIntersectionWithAllSourcesForInterval(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays = [],
  ) {
    $out  = [];
    $data = array_merge(
      $this->blocks->getNormalBlocksDataOverPeriod(
        $area_id,
        $date_start,
        $date_finish,
        $time_start,
        $time_finish
      ),
      $this->blocks->getPeriodicalBlocksDataOverPeriod(
        $area_id,
        $date_start,
        $date_finish,
        $time_start,
        $time_finish,
        $weekdays
      ),
      $this->blocks->getUnlimitedBlocksDataOverPeriod(
        $area_id,
        $date_start,
        $time_start,
        $time_finish,
        $weekdays
      ),
      $this->getReservationsDataOverPeriod(
        $area_id,
        $date_start,
        $date_finish,
        $time_start,
        $time_finish,
        $weekdays
      ),
      $this->tickets->getAbosDataOverPeriod(
        $area_id,
        $date_start,
        $date_finish,
        $time_start,
        $time_finish,
        $weekdays
      )
    );

    $this->areas->getAreaData($area_id, $area);
    foreach ($data as $item) {
      $out[] = $this->formatIntersectionItemMessage($item, $area);
    }

    return $out;
  }

  public function getHolidaySundayTimesConflictMessages(string $date, int $sourceWeekday, int $targetWeekday): array
  {
    if ($sourceWeekday === $targetWeekday) {
      return [];
    }

    $messages        = [];
    $workTimePeriods = $this->areas->getWorkTimePeriods();
    $areas_data      = [];
    if (!$this->areas->getAllAreasData($areas_data, false)) {
      return $messages;
    }

    foreach ($areas_data as $area) {
      $areaId      = (int)$area['area_id'];
      $period      = (int)$area['period'];
      $sourceSlots = $workTimePeriods[$areaId][$sourceWeekday] ?? [];
      $targetSlots = $workTimePeriods[$areaId][$targetWeekday] ?? [];
      if (empty(array_diff($sourceSlots, $targetSlots)) && empty(array_diff($targetSlots, $sourceSlots))) {
        continue;
      }

      $data = array_merge(
        $this->blocks->getNormalBlocksDataOverPeriod($areaId, $date, $date, '00:00', '23:59'),
        $this->blocks->getPeriodicalBlocksDataOverPeriod($areaId, $date, $date, '00:00', '23:59', []),
        $this->blocks->getUnlimitedBlocksDataOverPeriod($areaId, $date, '00:00', '23:59', []),
        $this->getReservationsDataOverPeriod($areaId, $date, $date, '00:00', '23:59', []),
        $this->tickets->getAbosDataOverPeriod($areaId, $date, $date, '00:00', '23:59', [])
      );

      foreach ($data as $item) {
        if (!$this->isEventWithinWeekdaySlots($item, $targetSlots, $period)) {
          $messages[] = $this->formatIntersectionItemMessage($item, $area);
        }
      }
    }

    return $messages;
  }

  protected function isEventWithinWeekdaySlots(array $item, array $targetSlots, int $period): bool
  {
    if (empty($targetSlots) || $period < 1) {
      return false;
    }

    $targetSet  = array_flip($targetSlots);
    $timeStart  = substr((string)($item['time_start'] ?? ''), 0, 5);
    $timeFinish = substr((string)($item['time_finish'] ?? ''), 0, 5);
    if ($timeStart === '' || $timeFinish === '' || $timeStart >= $timeFinish) {
      return true;
    }

    foreach (TimeHelper::generateArrayTimeInIncrements($timeStart, $timeFinish, $period, false) as $slot) {
      if (!isset($targetSet[$slot])) {
        return false;
      }
    }

    return true;
  }

  protected function formatIntersectionItemMessage(array $item, array $area): string
  {
    switch ($item['type']) {
      case 'PeriodicalBlock':
        $type = lang('blocks_periodical_title_one', 'message_notify');
        break;
      case 'NormalBlock':
        $type = lang('blocks__title_one', 'message_notify');
        break;
      case 'UnlimitedBlock':
        $type = lang('blocks_unlimited_title_one', 'message_notify');
        break;
      case 'Reservations':
        $type = lang('Booking client', 'message_notify');
        break;
      case 'Abo':
        $type = lang('Abo client', 'message_notify');
        break;
      default:
        $type = '';
    }

    $outStr = lang('Attention, one already exists',
        'message_error') . ' ' . $type . ' "<b>' . $item['name'] . '</b>" ' . ($item['type'] != 'UnlimitedBlock' ? lang('time_lang_out') : lang('from')) . ' <b>' . date(
        'd.m.Y',
        strtotime(
          $item['date_start']
        )
      ) . ($item['type'] != 'UnlimitedBlock' && $item['date_start'] != $item['date_finish'] ? '-' . date(
          'd.m.Y',
          strtotime($item['date_finish'])
        ) : '') . ' ' . $area['type_title'] . ' - ' . $area['title'] . ', ' . date(
        'H:i',
        strtotime($item['time_start'])
      ) . '-' . date('H:i', strtotime($item['time_finish'])) . '</b> ' . lang('hour_lang_out');
    if (!empty($item['weekdays'])) {
      if (is_string($item['weekdays'])) {
        $item['weekdays'] = explode(',', $item['weekdays']);
      }
      if (is_array($item['weekdays'])) {
        $outStr .= ' ' . lang('Weekdays') . ':';
        foreach ($item['weekdays'] as $weekday) {
          $outStr .= ' ' . TranslateHelper::translateWeekday($weekday, true);
        }
      }
    }

    return $outStr;
  }

  public function obtainIntersectionWithAllSourcesForIntervalForAllAreas(
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays = []
  ) {
    $out        = [];
    $areas_data = [];

    if (!$this->areas->getAllAreasData($areas_data, false)) {
      return $out;
    }

    foreach ($areas_data as $area) {
      $out = array_merge(
        $out,
        $this->obtainIntersectionWithAllSourcesForInterval(
          $area['area_id'],
          $date_start,
          $date_finish,
          $time_start,
          $time_finish,
          $weekdays
        )
      );
    }

    return $out;
  }

  public function getReservationsDataOverPeriod(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays,
  ) {
    $days   = DateHelper::getWorkingDaysForPeriod($date_start, $date_finish, $weekdays);
    $out    = [];
    $start  = $date_start . ' ' . $time_start;
    $finish = $date_finish . ' ' . $time_finish;

    $query = 'select r.*, concat(c.surname, \' \',c.name) as name, substring(r.start,1,10) as date_start, substring(r.finish,1,10) as date_finish, substring(r.start,12,8) as time_start, substring(r.finish,12,8) as time_finish 
        from ' . Query::tableName('reservations') . ' as r
        left join  ' . Query::tableName('clients') . ' as c on c.client_id=r.client_id
				WHERE r.area_id = "' . $area_id . '" AND (
			      ("' . $finish . '">=r.start and
			      "' . $start . '"<=r.finish)) order by r.start';

    //проверяем периодические блокировки
    foreach (Query::sqlQuery($query) as $row) {
      if (empty($row['client_id']) && empty($row['name'])) {
        $row['name'] = $row['client_surname'] . ' ' . $row['client_name'] . ' (' . lang('Guest') . ')';
      }
      if (in_array($row['date_start'], $days)) {
        $row['type'] = "Reservations";
        $out[]       = $row;
      }
    }

    return $out;
  }

  protected function getTmpReservation($mysql_date, $areas_ids)
  {
    // ping нужен, чтобы отличить «Redis недоступен» от «нет блокировок»; иначе пустой ответ ушёл бы без MySQL-fallback
    if (RedisHelper::ping()) {
      $tmp   = [];
      $areas = DataService::areas();
      foreach (explode(',', $areas_ids) as $areaId) {
        if (!empty($areaId)) {
          foreach (TmpBlockingHelper::getOrderBlock($areaId, $mysql_date, $this->clients->current_client_data['client_id'] ?? null) as $time) {
            $tmp[] = [
              'area_id'     => $areaId,
              'time_start'  => $time,
              'time_finish' => TimeHelper::addMinutes2MySQLTime($time, $areas[$areaId]->period),
            ];
          }
        }
      }

      return $tmp;
    }

    return defined('COUNT_MINUTE_FOR_PAYPAL') && COUNT_MINUTE_FOR_PAYPAL ? Query::sqlQuery(
      'select r.area_id, substring(r.start,12,5) AS time_start, substring(r.finish,12,5) AS time_finish, r.ordered from '
      . Query::tableName('reservations_tmp_paypal') . ' r
        where r.area_id in (' . substr($areas_ids, 0, -1) . ') and ' .
      'r.start between "' . $mysql_date . ' 00:00:00" and "' . $mysql_date . ' 23:59:59" and r.pay_state is null'
      . ' and r.ordered + interval ' . COUNT_MINUTE_FOR_PAYPAL . ' minute >= now()'
      . ' order by r.start'
    ) : [];
  }

  public function getReservationsOnBasisOfTmp(array $tmp_r_ids = [], ?array &$reservation_data = null, ?array &$real_ids = null, bool $asCheck = true)
  {
    $checkFields = Service::query()::getDB()->checkField('real_reservation_id', 'reservations_tmp_paypal');
    $out         = [];
    if ($this->getReservationDataByIds($tmp_r_ids, $reservation_data, true)) {
      foreach ($reservation_data as $item) {
        if ($this->getReservationData($item['area_id'], $item['start'], $data_r)) {
          if ($checkFields && $item['real_reservation_id'] == $data_r['reservation_id']) {
            $real_ids[] = $data_r['reservation_id'];
          }
          $out[] = $data_r;
        }
      }
    }

    return $asCheck ? (bool)count($out) : $out;
  }

  protected function reservationDataEngine(): ReservationDataEngine
  {
    return getEngine('ReservationData');
  }

  public function renderReservationData(?array $data): array
  {
    $reservation_data = [];

    if (is_array($data)) {
      foreach ($data as $row) {
        if (!isset($reservation_data[$row['reservation_id']])) {
          $reservation_data[$row['reservation_id']] = $row;
          unset($reservation_data[$row['reservation_id']]['name'],
            $reservation_data[$row['reservation_id']]['value'],
            $reservation_data[$row['reservation_id']]['entry_id'],
            $reservation_data[$row['reservation_id']]['type_block']);
        }
        if (!empty($row['name']) && !empty($row['type_block']) && !empty($row['entry_id'])) {
          $reservation_data[$row['reservation_id']][$row['type_block']][$row['entry_id']][$row['name']] = $row['value'];
        }
      }
    }

    return $reservation_data;
  }

  protected function getStocksCodeByIds(array $stocksIds = []): array
  {
    /** @var array<StockDto> $allStocks */
    static $allStocks;
    if (empty($allStocks)) {
      $allStocks = ModCommHelper::get('stocks', 'stocks/getStocks', ['as' => 'dto'], 'stocks', []);
    }
    $stocksCodes = [];

    foreach ($stocksIds as $stockId) {
      if (isset($allStocks[$stockId])) {
        $stocksCodes[] = $allStocks[$stockId]->code;
      }
    }

    return $stocksCodes;
  }

  public function setPaymentStateReservations(string $reference, bool $tmp = true): bool
  {
    return (bool)Query::sqlQuery(
      'UPDATE ' . Query::tableName('reservations' . ($tmp ? '_tmp_paypal' : '')) . ' SET payment_state = "1" WHERE reference LIKE ?',
      [$reference . '%'],
      false
    );
  }

  public function setPayOneReference($reservation_id, $reference): bool
  {
    $params = [
      $reference,
      $reservation_id,
    ];
    if (Query::sqlQuery('update ' . Query::tableName('reservations_tmp_paypal') . ' set reference=? where reservation_id = ?',
      $params, false)) {
      return true;
    }

    return false;
  }

  /**
   * PayPal NVP: после {@see DoExpressCheckoutPayment} дописываем TRANSACTIONID к reference,
   * куда ранее (после SetExpressCheckout) записан TOKEN.
   */
  public function appendTmpPaypalReferenceTransactionId(string $paypalToken, string $transactionId): bool
  {
    $paypalToken   = trim($paypalToken);
    $transactionId = trim($transactionId);
    if ($paypalToken === '' || $transactionId === '') {
      return false;
    }
    $newRef = $paypalToken . '|' . $transactionId;

    return (bool)Query::sqlQuery(
      'UPDATE ' . Query::tableName('reservations_tmp_paypal') . ' SET reference = ? WHERE reference = ?',
      [$newRef, $paypalToken],
      false
    );
  }

  public function getReservationsTmpByReference($reference): array
  {
    if ($row = Query::sqlQuery('select * from ' . Query::tableName('reservations_tmp_paypal') . ' where reference LIKE ?', ["{$reference}%"])) {
      return $row;
    }

    return [];
  }

  public function unsetRealReservationIdInTmp($id): bool
  {
    if (Query::sqlQuery('update ' . Query::tableName('reservations_tmp_paypal') . ' set real_reservation_id=(NULL) where reservation_id = "' . (int)$id . '"',
      [], false)) {
      return true;
    }

    return false;
  }

  public function setPayPalStatusByReference($reference, $status)
  {
    $params = [$status, "{$reference}%"];
    Query::sqlQuery('update ' . Query::tableName('reservations_tmp_paypal') . ' set pay_state = ? where reference LIKE ?', $params, false);
  }

  public function setPayPalStatusByIds($Ids, $state)
  {
    $params = [$state, join(',', $Ids)];
    Query::sqlQuery('update ' . Query::tableName('reservations_tmp_paypal') .
      ' set pay_state = ? 
      where reservation_id IN (?)', $params, false);
  }

  public function getClientsReservationsBySport(?int $type_id = null, ?int $sport_id = null): array
  {
    $clients = $params = $where = [];
    if ($type_id) {
      $where[]  = 'a.type_id=?';
      $params[] = $type_id;
    }
    if ($sport_id) {
      $where[]  = 'a.sport_id=?';
      $params[] = $sport_id;
    }
    $q = 'select r.client_id,
        count(r.reservation_id) as count_reservations,
        max(r.start) as date_last_reservation,
        max(case when r.start < now() then r.start end) as date_last_played_reservation,
        min(case when r.start >= now() then r.start end) as date_next_reservation
      from '
      . Query::tableName('reservations') . ' r
          left join ' . Query::tableName('areas') . ' a on r.area_id = a.area_id'
      . (!empty($where) ? ' where ' . implode(' and ', $where) : '')
      . ' group by r.client_id';
    if ($rows = Query::sqlQuery($q, $params)) {
      foreach ($rows as $row) {
        $clients[$row['client_id']] = $row;
      }
    }

    return $clients;
  }
}
