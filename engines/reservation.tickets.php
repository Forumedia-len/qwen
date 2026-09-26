<?php

use AC\app\services\DataService;
use AC\core\engines\Engines;
use AC\core\modules\areas\entities\dto\AreaDto;
use AC\core\modules\config\entities\dto\ExtraDto;
use AC\core\modules\discounts\entities\dto\DiscountDto;
use AC\core\modules\discounts\entities\enums\TypeDiscount;
use AC\core\modules\tickets\entities\enums\TicketDisabledTimeSource;
use AC\core\modules\tickets\services\TicketBlockIntersectionService;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;


class reservation_tickets
{
  private ?bool $hasDisabledTimeSource = null;
  private array $disabledTimeCache = [];
  public $holidays;
  public $blocks;

  function initialize()
  {
  }

  function finalize()
  {
  }

  //добавить билет
  function insertTicket(
    $area_id,
    $client_id,
    $time_start,
    $time_finish,
    $weekdays,
    $space,
    $title,
    $discount_id,
    $period_start,
    $period_finish,
    &$error_code = 0,
    &$new_ticket_id = null
  ) {
    if ($this->checkInsertTicket(
      $area_id,
      $client_id,
      $time_start,
      $time_finish,
      $error_code
    )) {
      //проверка на хотя бы один выбранный день
      if (strlen($weekdays) > 0) {
        //есть выделенные дни
        $q = 'INSERT INTO ' . Query::tableName('tickets') . '
							(client_id, area_id, time_start, time_finish, weekdays, space, title, discount_id, week_begin, date_create, date_modified, period_start, period_finish) VALUES
							(' . $client_id . ', ' . $area_id . ', "' . addslashes($time_start) . '", "' . addslashes(
            $time_finish
          ) . '", "' . $weekdays . '", "' . $space . '", "' . addslashes(
            $title
          ) . '", "' . $discount_id . '", week("' . $period_start . '", 3), now(),now(), "' . $period_start . '", "' . $period_finish . '")';
        Query::sqlQuery($q, [], false);
        $new_ticket_id = Query::getLastId();

        return true;
      } else {
        //нет активных дней недели
        $error_code = 4;

        return false;
      }
    }
    return false;
  }

  /**
   * Проверяет наличие поля источника исключения и кэширует результат на запрос.
   *
   * @return bool
   */
  public function hasDisabledTimeSource(): bool
  {
    if ($this->hasDisabledTimeSource === null) {
      try {
        $this->hasDisabledTimeSource = Query::getDB()->checkField('source_type', 'tickets_disabled_time');
      } catch (Throwable) {
        $this->hasDisabledTimeSource = false;
      }
    }

    return $this->hasDisabledTimeSource;
  }

  protected function checkInsertTicket(
    $area_id,
    $client_id,
    $time_start,
    $time_finish,
    &$error_code,
  ): bool {
    $error_code = 0;
    //есть ли такая площадка?
    if (Query::sqlQuery('SELECT count(*) as cnt FROM ' . Query::tableName('areas') . ' WHERE area_id = :area_id', [':area_id' => $area_id], true,
        ['onlyOne' => true])['cnt'] > 0) {
      //площадка есть
      //есть ли клиент
      if (Query::sqlQuery(
          'SELECT count(*) as cnt FROM ' . Query::tableName('clients') . ' WHERE client_id = ' . $client_id . ' and mode in (1,2)', [], true,
          ['onlyOne' => true]
        )['cnt'] > 0) {
        //клиент есть
        if (date('H:i', strtotime($time_start)) == $time_start
          && date('H:i', strtotime($time_finish)) == $time_finish &&
          strtotime($time_start) < strtotime($time_finish)
        ) {
          //время корректно

          return true;
        } else {
          //начальное/конечное время некорректно
          $error_code = 3;

          return false;
        }
      } else {
        //клиент не найден
        $error_code = 2;

        return false;
      }
    }
    //площадки нет
    $error_code = 1;

    return false;
  }


  //добавить билет фитнес
  function insertTicketFit(
    $area_id,
    $client_id,
    $time_start,
    $time_finish,
    $space,
    $title,
    $discount_id,
    $period_start,
    $price_for_month = null,
    $count_month = 0,
    &$error_code = 0,
    &$new_ticket_id = null
  ) {

    if ($this->checkInsertTicket(
      $area_id,
      $client_id,
      $time_start,
      $time_finish,
      $error_code
    )) {
      $q = 'INSERT INTO ' . Query::tableName('tickets') . '
							(client_id, area_id, time_start, time_finish, space, title, discount_id, week_begin, date_create, date_modified, period_start, price_for_month, count_month) VALUES
							(' . $client_id . ', ' . $area_id . ', "' . addslashes($time_start) . '", "' . addslashes($time_finish)
        . '", "' . $space . '", "' . addslashes($title) . '", "' . $discount_id . '", week("' . $period_start . '", 3), now(),now(), "' . $period_start . '", "' . NumberHelper::float($price_for_month) . '", "' . $count_month . '")';
      if (Query::sqlQuery($q, [], false)) {
        $new_ticket_id = Query::getLastId();

        return true;
      }
    }

    return false;
  }


  //удалить билет
  function removeTicket($ticket_id)
  {
    //удаляем запись о деактивированном времени
    Query::sqlQuery('delete FROM ' . Query::tableName('tickets_disabled_time') . ' WHERE ticket_id = ' . $ticket_id, [], false);
    //удаляем запись о промежутках билета
    Query::sqlQuery('delete FROM ' . Query::tableName('tickets_periods') . ' WHERE ticket_id = ' . $ticket_id, [], false);
    //удаляем запись о билете
    Query::sqlQuery('delete FROM ' . Query::tableName('tickets') . ' WHERE ticket_id = ' . $ticket_id, [], false);
    $this->resetDisabledTimeCache();
  }

  //добавить билет
  function changeTicket($ticket_id, $client_id, $title, $discount_id, $price_for_month = null)
  {
    //есть ли клиент
    $q    = 'SELECT count(*) as cnt FROM ' . Query::tableName('clients') . ' WHERE client_id = ' . $client_id . ' and mode in(1,2)';
    $temp = Query::sqlQuery($q);
    if ($temp[0]['cnt'] > 0) {
      //клиент есть
      $q = 'update ' . Query::tableName('tickets') . '
				set
					client_id = ' . $client_id . ',
					title = "' . addslashes($title) . '",
					discount_id = "' . $discount_id . '",'
        . ($price_for_month ? ' price_for_month = "' . str_replace(',', '.', $price_for_month) . '",' : '')
        . ' date_modified = now()
				where ticket_id = ' . $ticket_id;
      Query::sqlQuery($q, [], false);

      return true;
    } else {
      //клиент не найден
      return false;
    }
  }

  //добавить свет для определенной игры в абонементе
  function insertLightHeatingTicket($ticket_id, $type, $date, $time_start, $weekday)
  {
    //берем цену за свет
    $q    = 'SELECT a.light_price, a.heating_price, a.net_price from ' . Query::tableName('tickets') . ' t
			    RIGHT JOIN ' . Query::tableName('areas') . ' a ON t.area_id = a.area_id
			    WHERE t.ticket_id = "' . $ticket_id . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $row = $temp[0];
      if (Query::sqlQuery(
        'insert into ' . Query::tableName('tickets_light') . '
			    set
				ticket_id = ' . $ticket_id . ',
				type = "' . $type . '",
				date = "' . $date . '",
				time_start = "' . $time_start . '",
				weekday = "' . $weekday . '",
				price = "' . ($type == 1 ? $row['light_price'] : ($type == 2 ? $row['heating_price'] : $row['net_price'])) . '"'
        ,
        [],
        false
      )) {
        return true;
      }
    }

    return false;
  }


  /* ВЫДАЧА */

  //площадки, на которые есть билеты
  function getTicketsAreas(&$areas)
  {
    $q     = 'select at.type_id, at.title as type_title, a.area_id, a.sport_id, a.title as area_title, count(t.ticket_id) as cnt, asp.title as sport_title 
			from ' . Query::tableName('areas') . ' a 
			join ' . Query::tableName('areas_types') . ' at ON at.type_id IS NOT NULL 
			join ' . Query::tableName('areas_sports') . ' asp ON asp.sport_id IS NOT NULL
			left join ' . Query::tableName('tickets') . ' t on t.area_id = a.area_id
			where a.type_id = at.type_id and t.area_id is not null and a.sport_id = asp.sport_id'
      . Service::engines()->areas->sqlCheckGroupType('at')
      . ' group by a.area_id
			order by at.sort, a.sort';
    $areas = Query::sqlQuery($q);
    if (!empty($areas)) {
      return true;
    } else {
      $areas = null;

      return false;
    }
  }

  //данные о имеющихся билетах на заданную площадку
  //0 - по area_id
  //1 - последние 20 зарегестрированных
  //2 - истекшие
  //3 - все
  function getTicketsData($mode, $area_id, &$tickets_data)
  {
    if ($mode == 0) {//по area_id
      $q = 'select t.*, a.title as area, at.title as area_type, asp.title as area_sport,
						c.name as client_name, 
						c.surname as client_surname, 
						c.discount as client_discount,
						c.address as client_address, 
						c.post_code as client_post_code, 
						c.city as client_city, 
                                                c.email as client_email,
					 min(tp.start) as date_start, max(tp.finish) as date_finish 
				from ' . Query::tableName('tickets') . ' t 
				join ' . Query::tableName('clients') . ' c ON c.client_id IS NOT NULL 
				left join ' . Query::tableName('tickets_periods') . ' tp on tp.ticket_id = t.ticket_id
				left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
				left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
				left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
				where t.client_id = c.client_id and t.area_id = ' . $area_id
        . Service::engines()->areas->sqlCheckGroupType('at')
        . ' group by t.ticket_id
				order by c.surname, c.name';
    } elseif ($mode == 1 || $mode == 2) {
      $sq = 'CREATE TEMPORARY TABLE tmp
				select a.title as area, at.title as area_type, asp.title as area_sport, t.*, 
						c.name as client_name, 
						c.surname as client_surname, 
						c.discount as client_discount, 
						c.address as client_address, 
						c.post_code as client_post_code, 
						c.city as client_city, 
                                                c.email as client_email,
				min(tp.start) as date_start, max(tp.finish) as date_finish
				from ' . Query::tableName('tickets') . ' t 
        join ' . Query::tableName('clients') . ' c ON c.client_id IS NOT NULL 
				left join ' . Query::tableName('tickets_periods') . ' tp on tp.ticket_id = t.ticket_id
				left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
				left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
				left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
				where t.client_id = c.client_id'
        . Service::engines()->areas->sqlCheckGroupType('at')
        . (Service::engines()->areas->checkGroupType('fitness') && $mode == 2 ? ' and tp.finish is not null ' : '')
        . ' group by t.ticket_id
				order by t.ticket_id desc';
      Query::sqlQuery($sq, [], false);
      if ($mode == 1) { //последние 20 зарегестрированных действующих
        $q = 'select * from tmp where date_finish > "' . date('Y-m-d') . '" or date_finish is null Limit 20';
      } elseif ($mode == 2) { // истекшие
        $q = 'select * from tmp where date_finish < "' . date('Y-m-d') . '" order by date_finish desc';
      }
    } elseif ($mode == 3) {
      //все
      $q = 'select a.title as area, at.title as area_type, asp.title as area_sport, t.*, 
					c.name as client_name, 
					c.surname as client_surname, 
					c.discount  as client_discount,
					c.address as client_address, 
					c.post_code as client_post_code, 
					c.city as client_city, 
                                        c.email as client_email,
				min(tp.start) as date_start, max(tp.finish) as date_finish
				from ' . Query::tableName('tickets') . ' t 
        join ' . Query::tableName('clients') . ' c ON c.client_id IS NOT NULL 
				left join ' . Query::tableName('tickets_periods') . ' tp on tp.ticket_id = t.ticket_id
				left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
				left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
				left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
				where t.client_id = c.client_id'
        . Service::engines()->areas->sqlCheckGroupType('at')
        . ' group by t.ticket_id
				order by t.ticket_id desc';
    }

    $tickets_data = Query::sqlQuery($q);
    if (!empty($tickets_data)) {
      if ($mode == 2) //удаляем временную таблицу
      {
        Query::sqlQuery('drop table tmp', [], false);
      }

      return true;
    } else {
      $tickets_data = null;

      return false;
    }
  }

  //площадки, на которые есть билеты
  function getTicketsClients($client_id)
  {
    $q     = 'select t.*, tp.start, tp.finish , a.title as area_title, t.area_id, at.title as type_title from ' . Query::tableName('tickets') . ' t 
			left join ' . Query::tableName('tickets_periods') . ' tp on tp.ticket_id = t.ticket_id 
			inner join  ' . Query::tableName('areas') . ' a on a.area_id = t.area_id 
			inner join  ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id 
			where t.client_id = "' . $client_id . '"'
      . Service::engines()->areas->sqlCheckGroupType('at');
    $areas = Query::sqlQuery($q);
    if (!empty($areas)) {
      return $areas;
    } else {
      return false;
    }
  }

  //данные о билете с периодами
  function getTicketDataWithPeriodsById($ticket_id, &$ticket_data, &$periods_data)
  {
    $q    = 'select t.*, a.title as area, at.title as area_type, asp.title as area_sport, c.client_id, c.name as client_name, c.surname as client_surname
			from ' . Query::tableName('tickets') . ' t
			left join ' . Query::tableName('clients') . ' c on c.client_id = t.client_id
			left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
			left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
			left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
			where t.ticket_id="' . $ticket_id . '"';
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      $ticket_data   = $temp[0];
      $period_start  = $ticket_data['period_start'];
      $period_finish = $ticket_data['period_finish'];

      $periods_data = [];
      $q            = 'select *
				from ' . Query::tableName('tickets_periods') . '
				where ticket_id = "' . $ticket_id . '"
				order by start';
      $temp         = Query::sqlQuery($q);
      $i            = 0;
      foreach ($temp as $row) {
        $periods_data[] = $row;
        if ($period_start == null && $i == 0) {
          $ticket_data['period_start'] = $row['start'];
        }
        if ($period_finish == null) {
          $ticket_data['period_finish'] = $row['finish'];
        }
      }

      return true;
    } else {
      $ticket_data  = null;
      $periods_data = null;

      return false;
    }
  }

  //данные о билете с периодами и ценами
  function getTicketFullDataWithPeriodsAndPriceById($ticket_id, &$ticket_data = [], &$periods_data = [])
  {
    $engine = new Engines();
    $q      = 'select t.*, a.title as area, a.period, at.title as area_type,asp.title as area_sport, c.client_id, c.discount as client_discount, 
       c.name as client_name, c.surname as client_surname, ce.extra_id, ce.rate as extra, ce.use_time, 
       if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period,
        if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin,
       dc.ticket as discount_client, dc.dimension as discount_client_dimension, d.ticket as discount_ticket, d.dimension as discount_ticket_dimension,
       JSON_ARRAYAGG( JSON_OBJECT(\'ticket_id\', tp.ticket_id, \'period_id\', tp.period_id, \'start\', tp.start, \'finish\', tp.finish)) AS periods_data,
      (SELECT JSON_ARRAYAGG( JSON_OBJECT(
        \'ticket_id\', ticket_id, \'period_id\', period_id, \'time\', disabled_time, \'date\', disabled_date))
        FROM ' . Query::tableName('tickets_disabled_time') . ' WHERE ticket_id = t.ticket_id) AS disabled_time
			from ' . Query::tableName('tickets') . ' t
			INNER JOIN ' . Query::tableName('tickets_periods') . ' tp on t.ticket_id = tp.ticket_id
			INNER JOIN ' . Query::tableName('clients') . ' c on c.client_id = t.client_id
			INNER JOIN ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
      JOIN ' . Query::tableName('config_extra') . ' ce ON ce.club_state = c.club_state and ce.area_type_id = a.type_id and ce.area_sport_id = a.sport_id
			LEFT JOIN ' . Query::tableName('config_discount') . ' d on d.discount_id = t.discount_id
			LEFT JOIN ' . Query::tableName('config_discount') . ' dc on dc.discount_id = c.discount
			INNER JOIN ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
			INNER JOIN ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
			where t.ticket_id="' . $ticket_id . '"';
    $temp   = Query::sqlQuery($q);
    if (!empty($temp)) {
      $ticket_data = $temp[0];
      $prices_data = [];

      $q = 'select ap.start, ap.weekday, ap.price, ap.period_id from ' . Query::tableName('areas_prices') . ' ap
                            where ap.start >= "' . $ticket_data['time_start'] . '"
							AND ap.start < "' . $ticket_data['time_finish'] . '"
							AND ap.weekday in (' . $ticket_data['weekdays'] . ')
							AND ap.area_id = "' . $ticket_data['area_id'] . '"';

      $temp = Query::sqlQuery($q);

      foreach ($temp as $row) {
        if (!isset($ticket_data['sum_price'][$row['weekday']][$row['period_id']])) {
          $ticket_data['sum_price'][$row['weekday']][$row['period_id']] = 0;
        }
        $prices_data[$row['weekday']][$row['start']][$row['period_id']] = $row['price'];
        $ticket_data['sum_price'][$row['weekday']][$row['period_id']]   += $row['price'];
      }
      $ticket_data['price_info'] = $prices_data;

      $periods_data   = [];
      $this->holidays = new reservation_holidays ();
      $this->holidays->initialize();
      $this->blocks = new reservation_blocks ();
      $this->blocks->initialize();

      $q = 'select * from ' . Query::tableName('tickets_periods') . ' where ticket_id = "' . $ticket_id . '" order by start';

      $temp = Query::sqlQuery($q);

      foreach ($temp as $row) {
        $row['start']  = $row['start'] . ' ' . $ticket_data['time_start'];
        $row['finish'] = $row['finish'] . ' ' . $ticket_data['time_finish'];
        //найти праздники в данном промежутке
        $blocks_1 = $this->holidays->getHolidaysByDateInterval($row['start'], $row['finish']);
        //найти блокировки в данном промежутке
        $blocks_2 = $this->blocks->checkAreaDateTimePeriodsBlocked(
          $row['start'],
          $row['finish'],
          $ticket_data['area_id']
        );

        $blocks    = array_merge((is_array($blocks_1) ? $blocks_1 : []), (is_array($blocks_2) ? $blocks_2 : []));
        $sum_price = [];
        //расчитываем цену и сумму заказа
        $priceTemp = [];
        foreach ($prices_data as $weekday => $prices) {
          foreach ($prices as $time => $periods) {
            foreach ($periods as $period_id => $pr) {
              //Цена
              $tmp_price[$period_id]                           = $pr;
              $priceTemp[$weekday][$time][$period_id]['price'] = $pr;

              //Скидка для абонемента
              if ($ticket_data['discount_ticket_dimension'] == 1) {
                $ticket_discount = $tmp_price[$period_id] / 100 * $ticket_data['discount_ticket'];
              } else {
                $ticket_discount = $ticket_data['discount_ticket'];
              }
              $priceTemp[$weekday][$time][$period_id]['discount_ticket'] = $ticket_discount;

              //Цена с учетом скидки абонемента
              $tmp_price[$period_id] = $tmp_price[$period_id] - $ticket_discount;

              //Цена с учетом наценки для не участников клуба
              if ($ticket_data['use_time']) {
                $ticket_data['extra']                   = $engine->extra->getExtraWeekPrice($ticket_data['extra_id'], $weekday, $time);
                $ticket_data['extras'][$weekday][$time] = $ticket_data['extra'];
              }
              $tmp_price[$period_id]                           = $tmp_price[$period_id] + $ticket_data['extra'];
              $priceTemp[$weekday][$time][$period_id]['extra'] = $ticket_data['extra'];

              //Скидка для клиента
              if ($ticket_data['discount_client_dimension'] == 1) {
                $client_discount = $tmp_price[$period_id] / 100 * $ticket_data['discount_client'];
              } else {
                $client_discount = $ticket_data['discount_client'];
              }
              $priceTemp[$weekday][$time][$period_id]['discount_client'] = $client_discount;

              //Цена с учетом скидки клиента
              $tmp_price[$period_id] = round($tmp_price[$period_id] - $client_discount, 2);

              //Сама сумма
              $sum_price[$weekday][$time][$period_id] = $tmp_price[$period_id];
            }
          }
        }
        $row['price'] = $sum_price;

        foreach ($prices_data as $weekday => $price) {
          //получаем все дни за данный период
          $tmp_days = CalendarHelper::getCountWeekDays(
            $ticket_data['period'],
            $weekday,
            $row['start'],
            $row['finish'],
            $ticket_data['space'],
            (is_array($blocks) ? $blocks : ''),
            CalendarHelper::getFirstDayTicket($ticket_data['min_start_period'], $weekday, $ticket_data['week_begin'])
          );
          foreach ($tmp_days as $tmp_date => $tmp_times) {
            //tmp_date - дата формата 2019-03-06
            //tmp_times - массив занятых часов за данный день

            //берем всё деактивированное время за заданный день
            $dis_time_query = "SELECT * FROM " . Query::tableName('tickets_disabled_time') . " WHERE disabled_date= '" . $tmp_date . "'";
            $disabled_times = Query::sqlQuery($dis_time_query);

            foreach ($tmp_times as $tmp_time => $tmp_time_value) {
              //tmp_time - время формата 12:00:00
              //tmp_time_value - 1
              foreach ($disabled_times as $disabled_time_item) {
                //если найдено время за данный период и для данного билета, то исключаем его из выборки
                if ($disabled_time_item["ticket_id"] == $row["ticket_id"] &&
                  $disabled_time_item["disabled_time"] == $tmp_time) {
                  unset($tmp_days[$tmp_date][$tmp_time]);
                }
              }
            }
          }
          $row['count_game'][$weekday] = $tmp_days;
        }
        $periods_data[] = $row;
      }

      return true;
    } else {
      $ticket_data  = [];
      $periods_data = [];

      return false;
    }
  }

  /**
   * Вынесено из getTicketFullDataWithPeriodsAndPriceById
   * формирование данных для периодов абонемента
   * цены и игровые дни (дата и время)
   *
   * @param array      $ticket_data передаем данные билета
   * @param array|null $blocks
   * @param array|null $holidays
   *
   * @return bool
   * @throws Exception
   */
  public function renderTicketPeriods(array &$ticket_data, ?array $blocks = null, ?array $holidays = null): bool
  {
    if (!empty($ticket_data)) {
      $periods_data = [];
      if ($periods = JsonHelper::decode($ticket_data['periods_data'], true) ?? []) {
        $periods = Query::sqlQuery('select * from ' . Query::tableName('tickets_periods') . ' where ticket_id = "' . $ticket_data['ticket_id'] . '" order by start');
      }
      /** @var AreaDto $area */
      $area      = DataService::areas()[$ticket_data['area_id']];
      $workTimes = $this->renderTicketTime($ticket_data['time_start'], $ticket_data['time_finish'], $area->period, []);
      $discounts = DataService::discounts();
      /** @var ExtraDto $extra */
      $extra = DataService::extra()[$area->typeSportAsString()][$ticket_data['client_club_state']];
      if (!empty($ticket_data['disabled_time']) && is_string($ticket_data['disabled_time'])) {
        $tmpDesTime                   = JsonHelper::decode($ticket_data['disabled_time'], true) ?? [];
        $ticket_data['disabled_time'] = [];
        foreach ($tmpDesTime as $desTime) {
          $ticket_data['disabled_time'][$desTime['date']][] = TimeHelper::convertTime24($desTime['time'], false);
        }
      }
      foreach ($periods as $periodRow) {
        $periodRow['start']  = $periodRow['start'] . ' ' . $ticket_data['time_start'];
        $periodRow['finish'] = $periodRow['finish'] . ' ' . $ticket_data['time_finish'];
        //найти праздники в данном промежутке
        $blocks_1 = $holidays ?? Service::engines()->holidays->getHolidaysByDateInterval($periodRow['start'], $periodRow['finish']);
        //найти блокировки в данном промежутке
        $blocks_2 = $blocks ? ($blocks[$area->areaId] ?? []) : Service::engines()->blocks->checkAreaDateTimePeriodsBlocked(
          $periodRow['start'],
          $periodRow['finish'],
          $ticket_data['area_id']
        );

        $blocks = array_merge((is_array($blocks_1) ? $blocks_1 : []), (is_array($blocks_2) ? $blocks_2 : []));

        $sum_price               = [];
        $disableTime             = $ticket_data['disabled_time'] ?? $this->getDisabledTime($periodRow['start'], $periodRow['finish'], null,
          $ticket_data['ticket_id']);
        $periodRow['count_game'] = [];
        //расчитываем цену и сумму заказа
        foreach (explode(',', $ticket_data['weekdays']) as $weekday) {
          foreach (array_keys($area->getseasons()) as $season) {
            foreach (array_keys($workTimes) as $time) {
              //Цена
              $tmp_price[$season] = $area->getPrice($season, (int)$weekday, $time);
              /** @var DiscountDto $ticket_discount Скидка для абонемента */
              if ($ticket_discount = $discounts[$ticket_data['ticket_discount_id']] ?? null) {
                //Цена с учетом скидки абонемента
                $tmp_price[$season] = $ticket_discount?->changePrice($tmp_price[$season]) ?? $tmp_price[$season];
              }

              //Цена с учетом наценки для не участников клуба
              $tmp_price[$season] = $tmp_price[$season] + $extra->getAmount($weekday, $time);

              /** @var DiscountDto $client_discount Скидка для клиента */
              if ($client_discount = $discounts[$ticket_data['client_discount_id']] ?? null) {
                //Цена с учетом скидки абонемента
                $tmp_price[$season] = $client_discount?->changePrice($tmp_price[$season]) ?? $tmp_price[$season];
              }
              //Сама сумма
              $sum_price[$weekday][$time][$season] = $tmp_price[$season];
            }
          }
//          $tmp_days = CalendarHelper::getCountWeekDays(
//            $area->period,
//            $weekday,
//            $periodRow['start'],
//            $periodRow['finish'],
//            $ticket_data['space'],
//            $blocks,
//            CalendarHelper::getFirstDayTicket($ticket_data['min_start_period'], $weekday, $ticket_data['week_begin'])
//          );
//          foreach ($tmp_days as $tmp_date => $tmp_times) {
//            if (isset($ticket_data['find_start_data']) && isset($ticket_data['find_end_data'])
//              && (($ticket_data['find_start_data'] < $tmp_date && $ticket_data['find_end_data'] < $tmp_date) || ($ticket_data['find_start_data'] > $tmp_date && $ticket_data['find_end_data'] > $tmp_date))) {
//              unset($tmp_days[$tmp_date]);
//            }
//            //tmp_date - дата формата 2019-03-06
//            //tmp_times - массив занятых часов за данный день
//            foreach (array_keys($tmp_times) as $tmp_time) {
//              //tmp_time - время формата 12:00:00
//              //tmp_time_value - 1
//              if ($disableTime[$tmp_date] && in_array(TimeHelper::convertTime24($tmp_time, false), $disableTime[$tmp_date])) {
//                unset($tmp_days[$tmp_date][$tmp_time]);
//              }
//            }
//          }
//          if (!empty($tmp_days)) {
//            $periodRow['count_game'][$weekday] = $tmp_days;
//          }
        }
        $periodRow['price'] = $sum_price;
        if (!empty($periodRow['count_game'])) {
          $periods_data[$periodRow['period_id']] = $periodRow;
        }
      }
      $ticket_data['periods_data'] = $periods_data;
      return true;
    }

    return false;
  }

  public function getPricesTicket($ticket_data): array
  {
    $sum_price = [];
    /** @var AreaDto $area */
    $area      = DataService::areas()[$ticket_data['area_id']];
    $workTimes = $this->renderTicketTime($ticket_data['time_start'], $ticket_data['time_finish'], $area->period, []);
    $discounts = DataService::discounts();
    /** @var ExtraDto $extra */
    $extra = DataService::extra()[$area->typeSportAsString()][$ticket_data['client_club_state'] ?: 1];
    foreach (explode(',', $ticket_data['weekdays']) as $weekday) {
      foreach (array_keys($area->getseasons()) as $season) {
        foreach (array_keys($workTimes) as $time) {
          //Цена
          $tmp_price[$season] = $area->getPrice($season, (int)$weekday, $time);
          /** @var DiscountDto $ticket_discount Скидка для абонемента */
          if ($ticket_discount = $discounts[$ticket_data['ticket_discount_id']] ?? null) {
            //Цена с учетом скидки абонемента
            $tmp_price[$season] = $ticket_discount?->changePrice($tmp_price[$season]) ?? $tmp_price[$season];
          }

          //Цена с учетом наценки для не участников клуба
          $tmp_price[$season] = $tmp_price[$season] + $extra->getAmount($weekday, $time);

          /** @var DiscountDto $client_discount Скидка для клиента */
          if ($client_discount = $discounts[$ticket_data['client_discount_id']] ?? null) {
            //Цена с учетом скидки абонемента
            $tmp_price[$season] = $client_discount?->changePrice($tmp_price[$season]) ?? $tmp_price[$season];
          }
          //Сама сумма
          $sum_price[$weekday][$time][$season] = $tmp_price[$season];
        }
      }
    }

    return $sum_price;
  }

  public function getPriceTicketByDateTime($date, $time, array &$ticket_data): float
  {
    $temp_price = [];
    /** @var AreaDto $area */
    $area      = DataService::areas()[$ticket_data['area_id']];
    $discounts = DataService::discounts();
    /** @var ExtraDto $extra */
    $extra = DataService::extra()[$area->typeSportAsString()][$ticket_data['client_club_state']];

    $price                         = $area->getPriceByDateTime($date, $time);
    $temp_price['price']           = $price;
    $temp_price['ticket_discount'] = 0;
    $temp_price['client_discount'] = 0;
    $weekday                       = CalendarHelper::getWeekdayByUnixTime($date);
    /** @var DiscountDto $ticket_discount Скидка для абонемента */
    if ($ticket_discount = $discounts[$ticket_data['ticket_discount_id']] ?? null) {
      $temp_price['ticket_discount'] = $ticket_discount?->getDiscountPrice($price, TypeDiscount::Abo);
      //Цен:Aboтом скидки абонемента
      $price = $ticket_discount?->changePrice($price, TypeDiscount::Abo) ?? $price;
    }
    $extraPrice = $extra?->getAmount($weekday, $time) ?? 0;
    //Цена с учетом наценки для не участников клуба
    $price               += $extraPrice;
    $temp_price['extra'] = $extraPrice;

    /** @var DiscountDto $client_discount Скидка для клиента */
    if ($client_discount = $discounts[$ticket_data['client_discount_id']] ?? null) {
      $temp_price['client_discount'] = $client_discount?->getDiscountPrice($price, TypeDiscount::Abo);
      //Цена с учетом скидки абонемента
      $price = $client_discount?->changePrice($price, TypeDiscount::Abo) ?? $price;
    }
    $ticket_data['prices'][$date][$time] = $temp_price;
    //Сама сумма
    return NumberHelper::float($price);
  }

  public function buildTimeTickets($row, $des_times): array
  {
    $tickets_row  = [];
    $start        = $row['time_start'];
    $finish       = $row['time_finish'];
    $period       = $row['time_period'] ?? DataService::areas()[$row['area_id']]->period;
    $ticket_times = $this->renderTicketTime($start, $finish, $period, $des_times);
    $start_row    = [];
    $finish_row   = [];
    $i            = $j = 0;
    foreach ($ticket_times as $ticket_time => $check) {
      if ($check) {
        if ($i == 0) {
          $start_row[$j] = $ticket_time;
        }
        $finish_row[$j] = date('H:i', strtotime($ticket_time . ' + ' . $period . 'minute'));
        $i++;
      } else {
        if ($i != 0 && isset($start_row[$j])) {
          $i = 0;
          $j++;
        }
      }
    }
    foreach ($start_row as $key => $time_start) {
      $row['time_start']  = $time_start;
      $row['time_finish'] = $finish_row[$key];
      $tickets_row[]      = $row;
    }

    return $tickets_row;
  }

  public function renderTicketTime($start, $finish, $period, $des_times): array
  {
    $unix_start   = strtotime($start);
    $unix_finish  = strtotime($finish);
    $ticket_times = [];

    while ($unix_start < $unix_finish) {
      $current = date('H:i', $unix_start);
      $check   = true;
      if (in_array($current, $des_times)) {
        $check = false;
      }
      $ticket_times[$current] = $check;
      $unix_start             = mktime(
        date('H', $unix_start),
        (date('i', $unix_start) + $period)
      );
    }

    return $ticket_times;
  }

  public function checkAddPeriodToTicketByOtherTickets(
    $ticket_row,
    $start,
    $finish,
    &$error_code,
    &$crosses,
    array $additionalDisabledTimes = []
  )
  {
    //проверка на пересечение с другими билетами
    //формируем строку для проверки совпадений по дням недели begin
    $weekdays       = explode(',', $ticket_row['weekdays']);
    $where_weekdays = '';
    foreach ($weekdays as $i) {
      $where_weekdays .= 't.weekdays & ' . pow(2, $i) . ' || ';
    }
    $where_weekdays = '(' . substr($where_weekdays, 0, -4) . ')';

    //формируем строку ... end
    //запрос
    $q = 'select c.name, c.surname, t.ticket_id, tp.start as period_start, tp.finish as period_finish, t.time_start, t.time_finish, a.period as time_period, t.space, t.weekdays, 
         if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period,    
         if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin 
      from ' . Query::tableName('tickets_periods') . ' tp ' .
      'left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id ' .
      'left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id ' .
      'left join ' . Query::tableName('areas') . ' a on t.area_id = a.area_id ' .
      'left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id ' .
      'where ' .
      '"' . date('Y-m-d', strtotime($start)) . '" <= tp.finish and  "' . date('Y-m-d',
        strtotime($finish)) . '" >= tp.start and ' .//совпадения по дате
      $where_weekdays . ' and ' .//совпадение по дням недели
      '(t.time_finish > "' . $ticket_row['time_start'] . '" and t.time_start < "' . $ticket_row['time_finish'] . '") and ' .//совпадения по времени
      't.area_id = ' . $ticket_row['area_id'] . ' and ' .//совпадение по площадке
      'tp.ticket_id <> ' . $ticket_row['ticket_id'] //дугой билет
      . Service::engines()->areas->sqlCheckGroupType('at');//проверка по группе типов площадки


    //проверка на пересечение с другими билетами с учетом двух/одно недельных абонементов
    $insert_ticket         = true;
    $workDaysCurrentTicket = $this->getWorkingDaysAndTicketTimesInGivenPeriod(
      $ticket_row['ticket_id'],
      $ticket_row,
      $start,
      $finish,
      $additionalDisabledTimes,
      [['start' => $start, 'finish' => $finish]]
    );
    foreach (Query::sqlQuery($q) as $row) {
      if ($crossingDate = array_intersect_key(
        $this->getWorkingDaysAndTicketTimesInGivenPeriod($row['ticket_id'], $row, $start, $finish),
        $workDaysCurrentTicket
      )) {
        foreach (array_keys($crossingDate) as $day) {
          if (isset($workDaysCurrentTicket[$day]) && isset($crossingDate[$day]) && ($crossingTimes = array_intersect($crossingDate[$day],
              $workDaysCurrentTicket[$day]))) {
            $insert_ticket = false;
            foreach (TimeHelper::getTimeByStartFinish($crossingTimes, $row['time_period']) as $time) {
              $crosses[] = [
                'name'    => $row['name'],
                'surname' => $row['surname'],
                'start'   => $day,
                'time'    => '(' . $time . ')',
              ];
            }
          }
        }
      }
    }
    if ($insert_ticket) {
      //пересечений с другими билетами нет
      $error_code = 0;
      $crosses    = null;

      return true;
    } else {
      //пересечения с промежутками других билетов
      $error_code = 4;
      //формируем массив данных

      return false;
    }
  }

  public function checkAddPeriodToTicketByReservations(
    $ticket_row,
    $start,
    $finish,
    &$error_code,
    &$crosses,
    array $additionalDisabledTimes = []
  )
  {
    //проверяем на наличие одиночных заказов в новом промежутке (start, finish)
    //начало и конец рабочего промежутка считаются ВКЛЮЧАИТЕЛЬНО, т.е. абонемент с 3 по 5 включает в себя и 3, и 4, и 5 числа
    $q        = 'select * from ' . Query::tableName('reservations') . '
					where
						area_id = ' . $ticket_row['area_id'] . ' and
						weekday(start) in (' . $ticket_row['weekdays'] . ') and
						date(start) >= "' . $start . '" and
						date(start) <= "' . $finish . '" and
						start < DATE_ADD(DATE_FORMAT(start, "%Y-%m-%d"), INTERVAL "' . $ticket_row['time_finish'] . '" HOUR_SECOND)
						AND finish > DATE_ADD(DATE_FORMAT(start, "%Y-%m-%d"), INTERVAL "' . $ticket_row['time_start'] . '" HOUR_SECOND)';
    $temp     = Query::sqlQuery($q);
    $tmp_res  = [];
    $workDaysCurrentTicket = $this->getWorkingDaysAndTicketTimesInGivenPeriod(
      $ticket_row['ticket_id'],
      $ticket_row,
      $start,
      $finish,
      $additionalDisabledTimes,
      [['start' => $start, 'finish' => $finish]]
    );
    foreach ($temp as $row) {
      $reservationStart = strtotime($row['start']);
      $reservationFinish = strtotime($row['finish']);
      $reservationDate = date('Y-m-d', $reservationStart);
      foreach ($workDaysCurrentTicket[$reservationDate] ?? [] as $ticketTime) {
        $slotStart = strtotime($reservationDate . ' ' . $ticketTime);
        $slotFinish = strtotime('+' . (int)$ticket_row['time_period'] . ' minutes', $slotStart);
        if ($slotStart < $reservationFinish && $slotFinish > $reservationStart) {
          $tmp_res[] = $row;
          break;
        }
      }
    }
    if (count($tmp_res) == 0) {
      //пересечений с одиночными заказами нет
      $error_code = 0;
      $crosses    = null;

      return true;
    } else {
      //есть пересечения с имеющимися резервированиями
      $error_code = 3;
      //формируем массив данных
      $crosses = $tmp_res;

      return false;
    }
  }

  public function checkAddPeriodToTicket(
    $ticket_id,
    $start,
    $finish,
    &$error_code,
    &$crosses,
    array $additionalDisabledTimes = []
  )
  {
    $error_code = 0;

    //проверяем на наличие билета
    $q    = 'select t.*, a.period as time_period, t.period_start as min_start_period
			from ' . Query::tableName('tickets') . ' t
			left join ' . Query::tableName('areas') . ' a on t.area_id = a.area_id
			where t.ticket_id = ' . $ticket_id;
    $temp = Query::sqlQuery($q);
    if (!empty($temp)) {
      //билет есть
      $ticket_row = $temp[0];

      $start_unixtime  = strtotime($start);
      $finish_unixtime = strtotime($finish);

      //проверяем дату на корректность
      if ($start == date('Y-m-d', $start_unixtime) && $finish == date(
          'Y-m-d',
          $finish_unixtime
        ) && $start_unixtime <= $finish_unixtime) {
        //дата корректна
        $this->checkAddPeriodToTicketByReservations(
          $ticket_row,
          $start,
          $finish,
          $error_code_r,
          $crosses_r,
          $additionalDisabledTimes
        );
        $this->checkAddPeriodToTicketByOtherTickets(
          $ticket_row,
          $start,
          $finish,
          $error_code_t,
          $crosses_t,
          $additionalDisabledTimes
        );
        if ($error_code_r == 0 && $error_code_t == 0) {
          $error_code = 0;
          $crosses    = null;

          return true;
        }
        $errors = [];
        $crossesByError = [];
        if ($error_code_r !== 0) {
          $errors[] = $error_code_r;
          $crossesByError['crosses_' . $error_code_r] = $crosses_r;
        }
        if ($error_code_t !== 0) {
          $errors[] = $error_code_t;
          $crossesByError['crosses_' . $error_code_t] = $crosses_t;
        }
        if (count($errors) === 1) {
          $error_code = $errors[0];
          $crosses = $crossesByError['crosses_' . $error_code];
        } else {
          $error_code = $errors;
          $crosses = $crossesByError;
        }

        return false;
      } else {
        //даты некорректны
        $error_code = 2;

        return false;
      }
    } else {
      //билет не найден
      $error_code = 1;
      $crosses    = null;

      return false;
    }
  }

  /**
   * Добавляет период абонемента с учётом существующих блокировок.
   *
   * Уже сохранённые даты не пересчитываются: блокировки применяются только к
   * календарным диапазонам, которые фактически добавляются этим вызовом.
   *
   * @param int        $ticket_id
   * @param string     $start
   * @param string     $finish
   * @param mixed      $error_code
   * @param mixed|null $crosses
   *
   * @return bool
   */
  public function addPeriodToTicket($ticket_id, $start, $finish, &$error_code, &$crosses): bool
  {
    $error_code = 0;
    $crosses = null;
    $ticket = Query::sqlQuery(
      'SELECT t.*, a.period AS time_period, t.period_start AS min_start_period '
      . 'FROM ' . Query::tableName('tickets') . ' t '
      . 'LEFT JOIN ' . Query::tableName('areas') . ' a ON a.area_id = t.area_id '
      . 'WHERE t.ticket_id = :ticket_id',
      [':ticket_id' => (int)$ticket_id],
      true,
      ['onlyOne' => true]
    );
    if (empty($ticket)) {
      $error_code = 1;
      return false;
    }
    $startUnixTime = strtotime($start);
    $finishUnixTime = strtotime($finish);
    if ($start !== date('Y-m-d', $startUnixTime)
      || $finish !== date('Y-m-d', $finishUnixTime)
      || $startUnixTime > $finishUnixTime) {
      $error_code = 2;
      return false;
    }

    $periodSnapshot = Query::sqlQuery(
      'SELECT period_id, ticket_id, start, finish FROM ' . Query::tableName('tickets_periods')
      . ' WHERE ticket_id = :ticket_id ORDER BY start, finish, period_id',
      [':ticket_id' => (int)$ticket_id]
    );
    if (!is_array($periodSnapshot)) {
      $error_code = 5;
      return false;
    }
    try {
      $newRanges = DateHelper::subtractRanges($start, $finish, $periodSnapshot);
    } catch (Throwable) {
      $error_code = 5;
      return false;
    }
    if ($newRanges === []) {
      return true;
    }
    try {
      $blocks = Service::engines()->blocks->checkAreaDateTimePeriodsBlocked(
        $start . ' ' . $ticket['time_start'],
        $finish . ' ' . TimeHelper::convertTime24($ticket['time_finish'], false),
        (int)$ticket['area_id']
      );
      $blocks = is_array($blocks) ? $blocks : [];
      $firstGameDates = CalendarHelper::getFirstDayTicket(
        $ticket['period_start'],
        $ticket['weekdays'],
        $ticket['week_begin'],
        false
      );
      $calculatedPeriods = [];
      $partiallyBlockedSlots = [];
      foreach ($newRanges as $range) {
        $result = (new TicketBlockIntersectionService())->calculate(
          $range['start'],
          $range['finish'],
          explode(',', $ticket['weekdays']),
          $ticket['space'],
          $firstGameDates,
          $ticket['time_start'],
          TimeHelper::convertTime24($ticket['time_finish'], false),
          (int)$ticket['time_period'],
          $blocks
        );
        array_push($calculatedPeriods, ...$result->getPeriods());
        foreach ($result->getPartiallyBlockedSlots() as $date => $times) {
          $partiallyBlockedSlots[$date] = array_values(array_unique(array_merge(
            $partiallyBlockedSlots[$date] ?? [],
            array_map(static fn(string $time): string => date('H:i', strtotime($time)), $times)
          )));
        }
      }
    } catch (Throwable) {
      $error_code = 5;
      return false;
    }

    if ($calculatedPeriods === []) {
      $error_code = 6;
      return false;
    }

    foreach ($calculatedPeriods as $period) {
      if (!$this->checkAddPeriodToTicket(
        $ticket_id,
        $period['start'],
        $period['finish'],
        $error_code,
        $crosses,
        $partiallyBlockedSlots
      )) {
        return false;
      }
    }

    $disabledPeriodSnapshot = Query::sqlQuery(
      'SELECT disabled_time_id, period_id FROM ' . Query::tableName('tickets_disabled_time')
      . ' WHERE ticket_id = :ticket_id',
      [':ticket_id' => (int)$ticket_id]
    );
    if (!is_array($disabledPeriodSnapshot)) {
      $error_code = 5;
      return false;
    }
    $newDisabledTimeIds = [];
    foreach ($calculatedPeriods as $period) {
      if (!$this->insertOrMergeTicketPeriod($ticket_id, $period['start'], $period['finish'])) {
        $this->restoreTicketPeriodState($ticket_id, $periodSnapshot, $disabledPeriodSnapshot, $newDisabledTimeIds);
        $error_code = 5;
        $crosses = null;
        return false;
      }
    }

    foreach ($partiallyBlockedSlots as $date => $times) {
      foreach ($times as $time) {
        $disabledError = 0;
        if (!$this->addDisabledTimeForTicket(
          $ticket_id,
          strtotime($date . ' ' . $time),
          $disabledError,
          0,
          TicketDisabledTimeSource::InitialBlock
        )) {
          $this->restoreTicketPeriodState($ticket_id, $periodSnapshot, $disabledPeriodSnapshot, $newDisabledTimeIds);
          $error_code = 5;
          $crosses = ['disabled_time_error' => $disabledError];
          return false;
        }
        $newDisabledTimeIds[] = (int)Query::getLastId();
      }
    }

    $this->resetDisabledTimeCache();
    return true;
  }

  /**
   * Добавляет диапазон и объединяет соприкасающиеся периоды с сохранением исключений.
   */
  private function insertOrMergeTicketPeriod(int $ticketId, string $start, string $finish): bool
  {
    $touchingPeriods = Query::sqlQuery(
      'SELECT period_id, start, finish FROM ' . Query::tableName('tickets_periods')
      . ' WHERE ticket_id = :ticket_id AND start <= :finish_next AND finish >= :start_previous'
      . ' ORDER BY start, finish, period_id',
      [
        ':ticket_id' => $ticketId,
        ':finish_next' => date('Y-m-d', strtotime('+1 day', strtotime($finish))),
        ':start_previous' => date('Y-m-d', strtotime('-1 day', strtotime($start))),
      ]
    );
    if (!is_array($touchingPeriods)) {
      return false;
    }
    if ($touchingPeriods === []) {
      if (!Query::sqlQuery(
        'INSERT INTO ' . Query::tableName('tickets_periods') . ' (ticket_id, start, finish)'
        . ' VALUES (:ticket_id, :start, :finish)',
        [':ticket_id' => $ticketId, ':start' => $start, ':finish' => $finish],
        false
      )) {
        return false;
      }

      return $this->assignDisabledTimesToTicketPeriod(
        $ticketId,
        (int)Query::getLastId(),
        $start,
        $finish
      );
    }

    $keeperId = (int)$touchingPeriods[0]['period_id'];
    $mergedStart = $start;
    $mergedFinish = $finish;
    foreach ($touchingPeriods as $period) {
      $mergedStart = min($mergedStart, $period['start']);
      $mergedFinish = max($mergedFinish, $period['finish']);
    }
    if (!$this->assignDisabledTimesToTicketPeriod($ticketId, $keeperId, $mergedStart, $mergedFinish)) {
      return false;
    }
    foreach ($touchingPeriods as $period) {
      $periodId = (int)$period['period_id'];
      if ($periodId === $keeperId) {
        continue;
      }
      if (!Query::sqlQuery(
        'DELETE FROM ' . Query::tableName('tickets_periods') . ' WHERE period_id = :period_id',
        [':period_id' => $periodId],
        false
      )) {
        return false;
      }
    }

    return (bool)Query::sqlQuery(
      'UPDATE ' . Query::tableName('tickets_periods')
      . ' SET start = :start, finish = :finish WHERE period_id = :period_id',
      [':start' => $mergedStart, ':finish' => $mergedFinish, ':period_id' => $keeperId],
      false
    );
  }

  /**
   * Связывает исключения с периодом по абонементу и дате, не доверяя сохранённому period_id.
   */
  private function assignDisabledTimesToTicketPeriod(
    int $ticketId,
    int $periodId,
    string $start,
    string $finish
  ): bool {
    return (bool)Query::sqlQuery(
      'UPDATE ' . Query::tableName('tickets_disabled_time')
      . ' SET period_id = :period_id'
      . ' WHERE ticket_id = :ticket_id AND disabled_date BETWEEN :start AND :finish',
      [
        ':period_id' => $periodId,
        ':ticket_id' => $ticketId,
        ':start' => $start,
        ':finish' => $finish,
      ],
      false
    );
  }

  /**
   * Восстанавливает периоды и связи исключений после ошибки записи в MyISAM.
   *
   * @param array<int, array<string, mixed>> $periodSnapshot
   * @param array<int, array<string, mixed>> $disabledPeriodSnapshot
   * @param array<int, int>                  $newDisabledTimeIds
   */
  private function restoreTicketPeriodState(
    int $ticketId,
    array $periodSnapshot,
    array $disabledPeriodSnapshot,
    array $newDisabledTimeIds
  ): void {
    foreach ($newDisabledTimeIds as $disabledTimeId) {
      Query::sqlQuery(
        'DELETE FROM ' . Query::tableName('tickets_disabled_time') . ' WHERE disabled_time_id = :disabled_time_id',
        [':disabled_time_id' => $disabledTimeId],
        false
      );
    }
    Query::sqlQuery(
      'DELETE FROM ' . Query::tableName('tickets_periods') . ' WHERE ticket_id = :ticket_id',
      [':ticket_id' => $ticketId],
      false
    );
    foreach ($periodSnapshot as $period) {
      Query::sqlQuery(
        'INSERT INTO ' . Query::tableName('tickets_periods') . ' (period_id, ticket_id, start, finish)'
        . ' VALUES (:period_id, :ticket_id, :start, :finish)',
        [
          ':period_id' => (int)$period['period_id'],
          ':ticket_id' => $ticketId,
          ':start' => $period['start'],
          ':finish' => $period['finish'],
        ],
        false
      );
    }
    foreach ($disabledPeriodSnapshot as $disabledTime) {
      Query::sqlQuery(
        'UPDATE ' . Query::tableName('tickets_disabled_time')
        . ' SET period_id = :period_id WHERE disabled_time_id = :disabled_time_id',
        [
          ':period_id' => (int)$disabledTime['period_id'],
          ':disabled_time_id' => (int)$disabledTime['disabled_time_id'],
        ],
        false
      );
    }
    $this->resetDisabledTimeCache();
  }

  //исключить период из билета
  function removePeriodFromTicket($ticket_id, $date_start, $date_finish, &$error_code, $admin = false)
  {
    $error_code = 0;
    //старт и финиш в timestamp
    $unixtime_start  = strtotime($date_start);
    $unixtime_finish = strtotime($date_finish);
    if ($admin || ($date_start == date('Y-m-d', $unixtime_start) && $date_finish == date(
          'Y-m-d',
          $unixtime_finish
        ) && $unixtime_start <= $unixtime_finish)) {
      //промежуток корректный

      //проверяем на наличие билета
      $q    = 'SELECT count(*) as cnt FROM ' . Query::tableName('tickets') . ' WHERE ticket_id = ' . $ticket_id;
      $temp = Query::sqlQuery($q, [], true, ['onlyOne' => true]);
      $cnt  = $temp['cnt'];
      if ($cnt > 0) {
        //билет есть

        //СТАРОЕ условие выборки пересекающихся промежутков
        //$where = '(("' . $date_start . '">=start AND "' . $date_start . '"<=finish)
        //	OR ("' . $date_finish . '">=start AND "' . $date_finish . '"<=finish)
        //	or (start > "' . $date_start . '" and start < "' . $date_finish . '")) and ticket_id="' . $ticket_id . '"';

        //определяем границы касающихся промежутков
        $q    = 'SELECT UNIX_TIMESTAMP(start) as start, UNIX_TIMESTAMP(finish) as finish, period_id ' .
          'FROM ' . Query::tableName('tickets_periods') . ' ' .
          'WHERE finish >= "' . $date_start . '" and  start <= "' . $date_finish . '" and ticket_id = ' . $ticket_id;
        $temp = Query::sqlQuery($q);
        if (!empty($temp)) {
          //перекрестные промежутки есть
          //массив запросов
          $q = [];
          foreach ($temp as $period_row) {
            //print_r($period_row);
            //обрабатываем пересекающийся промежуток
            //4 варианта (взаимоисключающие)
            if ($period_row['start'] < $unixtime_start && $period_row['finish'] > $unixtime_finish) {
              //промежуток с обоих сторон больше вырезаемого куска
              //дробим на 2 кусочка
              //урезаем 1й кусочек
              $q[] = 'update ' . Query::tableName('tickets_periods') . ' ' .
                'set finish = DATE_SUB("' . $date_start . '", interval 1 day) ' .
                'where period_id = ' . $period_row['period_id'];
              //добавляем 2й кусочек
              $second_period_part_query = 'insert into ' . Query::tableName('tickets_periods') . ' ' .
                '(ticket_id, start, finish) values ' .
                '("' . $ticket_id . '", DATE_ADD("' . $date_finish . '", interval 1 day), "' . date(
                  'Y-m-d',
                  $period_row['finish']
                ) . '")';

              //сразу делаем запрос на добавление нового периода, дабы сразу получить id
              Query::sqlQuery($second_period_part_query, [], false);
              $new_period_id = Query::getLastId();

              //?? START Disabled time block
              //находим деактивированное время за данный период
              //смотрим дату за которую время деактивированно
              $dis_time_query  = "SELECT * FROM " . Query::tableName('tickets_disabled_time') . " WHERE period_id='" . $period_row['period_id'] .
                "' AND ticket_id='" . $ticket_id . "'";
              $dis_time_result = Query::sqlQuery($dis_time_query);
              foreach ($dis_time_result as $dis_time_item) {
                $dis_time_unixtime = strtotime($dis_time_item["disabled_date"]);

                if ($dis_time_unixtime >= $period_row['start'] && $dis_time_unixtime < $unixtime_start) {
                  //если дата находится в старом периоде то оставляем все как есть
                } elseif ($dis_time_unixtime > $unixtime_finish && $dis_time_unixtime <= $period_row['finish']) {
                  //если дата находится в новом промежудке то меняем id периода
                  $q1 = "UPDATE " . Query::tableName(
                      'tickets_disabled_time'
                    ) . " SET period_id=" . $new_period_id . " WHERE disabled_time_id=" . $dis_time_item["disabled_time_id"];
                  Query::sqlQuery($q1);
                } elseif ($unixtime_start <= $dis_time_unixtime && $dis_time_unixtime <= $unixtime_finish) {
                  //если дата находится в исключаемом промежутке то удаляем запись об исключаемом времени
                  $delete_time_item_query = "DELETE FROM " . Query::tableName(
                      'tickets_disabled_time'
                    ) . " WHERE disabled_time_id=" . $dis_time_item["disabled_time_id"];
                  Query::sqlQuery($delete_time_item_query, [], false);
                }
              }
              //?? END Disabled time block
            } elseif ($period_row['start'] >= $unixtime_start && $period_row['finish'] <= $unixtime_finish) {
              //промежуток ВНУТРИ вырезаемого куска
              //удаляем промежуток
              $q[] = 'delete from ' . Query::tableName('tickets_periods') . '
								where period_id="' . $period_row['period_id'] . '"';

              //?? START Disabled time block
              //Удаляем запись о деактивированном времени
              $period_id      = $period_row["period_id"];
              $dis_time_query = "DELETE FROM " . Query::tableName('tickets_disabled_time') . " WHERE period_id=" . $period_id .
                " AND ticket_id=" . $ticket_id .
                " AND '" . $date_start . "' <= disabled_date AND '" . $date_finish . "' >= disabled_date";
              Query::sqlQuery($dis_time_query, [], false);
              //?? END Disabled time block

            } elseif ($period_row['start'] <= $unixtime_finish && $unixtime_start <= $period_row['start']) {
              //меняем начальную дату промежутка
              $q[] = 'update ' . Query::tableName('tickets_periods') . ' ' .
                'set start = "' . date('Y-m-d', strtotime($date_finish . '+1 day')) . '" ' .
                'where period_id = ' . $period_row['period_id'];

              //?? START Disabled time block
              //Проверяем есть ли деактивированное время до $date_finish
              //Если есть то удаляем его, если нет то оставляем все как есть
              $period_id      = $period_row["period_id"];
              $dis_time_query = "DELETE FROM " . Query::tableName('tickets_disabled_time') . " WHERE " .
                "period_id='" . $period_id .
                "' AND ticket_id='" . $ticket_id .
                "' AND '" . $date_finish . "' > disabled_date";
              Query::sqlQuery($dis_time_query, [], false);
              //?? END Disabled time block

            } elseif ($period_row['finish'] >= $unixtime_start && $unixtime_finish >= $period_row['finish']) {
              //меняем конечную дату промежутка
              $q[] = 'update ' . Query::tableName('tickets_periods') . ' ' .
                'set finish = "' . date('Y-m-d', strtotime($date_start . '-1 day')) . '" ' .
                'where period_id = ' . $period_row['period_id'];

              //?? START Disabled time block
              //Проверяем есть ли деактивированное время после $date_start
              //Если есть то удаляем его, если нет то оставляем все как есть
              $period_id      = $period_row["period_id"];
              $dis_time_query = "DELETE FROM " . Query::tableName('tickets_disabled_time') . " WHERE " .
                "period_id='" . $period_id .
                "' AND ticket_id='" . $ticket_id .
                "' AND '" . $date_start . "' < disabled_date";
              Query::sqlQuery($dis_time_query, [], false);
              //?? END Disabled time block
            }
          }
          if (!empty($q)) {
            //выполняем запросы
            foreach ($q as $query) {
              Query::sqlQuery($query, [], false);
            }

            $this->resetDisabledTimeCache();
            return true;
          } else {
            $error_code = 1;

            return false;
          }
        } else {
          //перекрестных промежутков НЕТ
          $error_code = 3;

          return false;
        }

        return true;
      } else {
        //билет не найден
        $error_code = 2;

        return false;
      }
    } else {
      //промежуток не корректный
      $error_code = 1;

      return false;
    }
  }

  //удалить период по его ID
  function removePeriodById($period_id)
  {
    Query::sqlQuery(
      'delete from ' . Query::tableName('tickets_disabled_time') . ' where period_id = ' . (int)$period_id,
      [],
      false
    );
    Query::sqlQuery(
      'delete from ' . Query::tableName('tickets_periods') . ' where period_id = ' . (int)$period_id,
      [],
      false
    );
    $this->resetDisabledTimeCache();

    return true;
  }

  //добавить деактивированное время билета
  function addDisabledTimeForTicket(
    $ticket_id,
    $datetime,
    &$error_code = 0,
    $price = 0,
    TicketDisabledTimeSource|int $sourceType = TicketDisabledTimeSource::Cancellation
  )
  {
    //Коды ошибок
    //0 - все нормально
    //1 - периода не существует
    //2 - такая запись времени уже есть
    //3 - билета не существует
    //4 - ошибка добавления записи в базу
    //5 - зоны не существует

    $source = $sourceType instanceof TicketDisabledTimeSource
      ? $sourceType
      : TicketDisabledTimeSource::tryFrom($sourceType) ?? TicketDisabledTimeSource::Cancellation;
    $sourceValue = $source->value;

    $date = date('Y-m-d', $datetime);
    $time = date_parse(date('H:i:s', $datetime));

    //проверяем наличе периода с данным id
    $period_query = 'SELECT UNIX_TIMESTAMP(start) as start, UNIX_TIMESTAMP(finish) as finish, period_id ' .
      'FROM ' . Query::tableName('tickets_periods') . ' ' .
      'WHERE finish >= "' . $date . '" and  start <= "' . $date . '" and ticket_id = ' . $ticket_id;

    $res = Query::sqlQuery($period_query);

    $period_item = $res[0];

    if (!$period_item) {
      //пустой элемент периода
      $error_code = 1;

      return false;
    }
    $period_id = $period_item["period_id"];

    //проверяем нет ли такого поля в базе
    $q = 'SELECT * ' .
      'FROM ' . Query::tableName('tickets_disabled_time') . ' ' .
      'WHERE ticket_id = ' . $ticket_id . ' AND disabled_time = "' . date(
        'H:i:s',
        $datetime
      ) . '" AND disabled_date= "' . $date . '"';

    $disabled_time_exist_result = Query::sqlQuery($q);

    if (!empty($disabled_time_exist_result)) {
      //Такая запись уже есть
      $error_code = 2;

      return false;
    }

    //проверяем наличие билета

    $q                  = 'SELECT * FROM ' . Query::tableName('tickets') . ' WHERE ticket_id = ' . $ticket_id;
    $tiket_query_result = Query::sqlQuery($q);
    $ticket_item        = $tiket_query_result[0];
    if (!$ticket_item) {
      //пустой элемент билета
      $error_code = 3;

      return false;
    }

    $area_id = $ticket_item["area_id"];

    //Берем зону из базы для получения значения периода (60 минут, 30 и т.д)
    $getAreaQuery  = 'SELECT * FROM ' . Query::tableName('areas') . ' WHERE area_id= ' . $area_id;
    $getAreaResult = Query::sqlQuery($getAreaQuery);
    $areaItem      = $getAreaResult[0];
    if (!$areaItem) {
      //пустой элемент зоны
      $error_code = 5;

      return false;
    }

    //конвертим дату с временем начала и конца в юникстайм
    $unixtime_ticket_time_start  = strtotime($date . ' ' . $ticket_item["time_start"]);
    $unixtime_ticket_time_finish = strtotime($date . ' ' . TimeHelper::convertTime24($ticket_item["time_finish"], false));

    //В исключение можно записать только начало слота внутри времени абонемента.
    if ($datetime >= $unixtime_ticket_time_start && $datetime < $unixtime_ticket_time_finish) {
      //записывать дату неиспользуемого времени
      $hasSource = $this->hasDisabledTimeSource();
      $query = 'INSERT INTO ' . Query::tableName('tickets_disabled_time')
        . ' (ticket_id, period_id, disabled_time, disabled_date, price, created_at, created_user'
        . ($hasSource ? ', source_type' : '') . ')'
        . ' VALUES (:ticket_id, :period_id, :disabled_time, :disabled_date, :price, :created_at, :created_user'
        . ($hasSource ? ', :source_type' : '') . ')';
      $params = [
        ':ticket_id' => (int)$ticket_id,
        ':period_id' => (int)$period_id,
        ':disabled_time' => date(
          'H:i:s',
          mktime($time['hour'], $time['minute'], $time['second'], $time['month'], $time['day'], $time['year'])
        ),
        ':disabled_date' => $date,
        ':price' => $price,
        ':created_at' => date('Y-m-d H:i:s'),
        ':created_user' => \Service::auth()->getTypeUserAndId(),
      ];
      if ($hasSource) {
        $params[':source_type'] = $sourceValue;
      }
      if (!Query::sqlQuery($query, $params, false)) {
        //Ошибка запроса к базе данных
        $error_code = 4;

        return false;
      }

      $this->resetDisabledTimeCache();
      return true;
    }
    $error_code = 4;

    return false;
  }

  //находим даты всех игр в пересекающийся билетах и в новосозданном
  function getDateEveryGameInTicket($start_unixtime, $finish_unixtime, $weekdays, $space, $first_games)
  {
    $weekdays         = !is_array($weekdays) ? explode(',', $weekdays) : $weekdays;
    $work_days        = [];
    $current_unixtime = strtotime(date('d-m-Y', $start_unixtime));
    while ($current_unixtime <= $finish_unixtime) {
      $current_weekday = CalendarHelper::getWeekdayByUnixtime($current_unixtime);

      if (in_array($current_weekday, $weekdays) && CalendarHelper::checkDayForTicket($current_unixtime, $first_games[$current_weekday], $space)) {
        $work_days[] = date('Y-m-d', $current_unixtime);
      }

      $current_unixtime = mktime(
        0,
        0,
        0,
        date('m', $current_unixtime),
        (date('d', $current_unixtime) + 1),
        date('Y', $current_unixtime)
      );
    }

    return $work_days;
  }

  /**
   *  Получить все рабочие дни и рабочие временные промежутки для абонемента.
   *  Если $ticketData пустой или не хватает нужных данных, запрашиваем эти данные из базы
   *
   * @param       $ticketId
   * @param array $ticketData - минимальный набор полей для работы
   *                          поиск идет по этим названиям полей:
   *                          ['period_start',   - дата начала периода;
   *                          'period_finish',   - дата окончания периода;
   *                          'time_start',      - время начала игры;
   *                          'time_finish',     - время окончания игры;
   *                          'time_period',     - количество минут одного периода;
   *                          'space',           - периодичность для недели;
   *                          'weekdays',        - дни недели;
   *                          'week_begin',      - значение недели в году;
   *                          'min_start_period'] - минимальное значение старта периода;
   *
   * @param       $periodStart
   * @param       $periodFinish
   * @param array      $additionalDisabledTimes Исключения, ещё не сохранённые в БД
   * @param array|null $activePeriods           Периоды, ещё не сохранённые в БД
   *
   * @return array
   */
  public function getWorkingDaysAndTicketTimesInGivenPeriod(
    $ticketId,
    $ticketData,
    $periodStart = null,
    $periodFinish = null,
    array $additionalDisabledTimes = [],
    ?array $activePeriods = null
  )
  {
    $outWorkDaysPeriod = [];

    $requiredFields = [
      'period_start',
      'period_finish',
      'time_start',
      'time_finish',
      'time_period',
      'space',
      'weekdays',
      'week_begin',
      'min_start_period',
    ];
    if (empty($ticketData) || count($requiredFields) != count(array_intersect($requiredFields, array_keys($ticketData)))) {
      $q          = 'select t.ticket_id, tp.start as period_start, tp.finish as period_finish, t.time_start, t.time_finish, a.period as time_period, t.space, t.weekdays, 
         if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period,    
         if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin 
      from ' . Query::tableName('tickets_periods') . ' tp ' .
        'left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id ' .
        'left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id ' .
        'left join ' . Query::tableName('areas') . ' a on t.area_id = a.area_id ' .
        ' where t.ticket_id=' . $ticketId;
      $ticketData = Query::sqlQuery($q, [], true, ['onlyOne' => true]);
    }

    $periodStart  = $periodStart ?: $ticketData['period_start'];
    $periodFinish = $periodFinish ?: $ticketData['period_finish'];
    $activePeriods ??= [[
      'start' => $ticketData['period_start'],
      'finish' => $ticketData['period_finish'],
    ]];
    $workDays     = $this->getDateEveryGameInTicket(
      strtotime($periodStart),
      strtotime($periodFinish),
      $ticketData['weekdays'],
      $ticketData['space'],
      CalendarHelper::getFirstDayTicket($ticketData['min_start_period'], $ticketData['weekdays'], $ticketData['week_begin'])
    );

    foreach ($workDays as $workDay) {
      $isActiveDay = false;
      foreach ($activePeriods as $activePeriod) {
        if ($activePeriod['start'] <= $workDay && $activePeriod['finish'] >= $workDay) {
          $isActiveDay = true;
          break;
        }
      }
      if ($isActiveDay) {
        $disabledTimes = array_values(array_unique(array_merge(
          $this->getDisabledTime($periodStart, $periodFinish, $workDay, $ticketId),
          $additionalDisabledTimes[$workDay] ?? []
        )));
        if ($times = $this->getWorkingTimeForEachDay($ticketData['time_start'], $ticketData['time_finish'], $ticketData['time_period'],
          $disabledTimes)) {
          $outWorkDaysPeriod[$workDay] = $times;
        }
      }
    }

    return $outWorkDaysPeriod;
  }


  public function getWorkingTimeForEachDay($start, $finish, $period, $disabledTimes = [])
  {
    $unix_start   = strtotime($start);
    $unix_finish  = strtotime($finish);
    $ticket_times = [];

    while ($unix_start < $unix_finish) {
      $current = date('H:i', $unix_start);
      if (empty($disabledTimes) || !in_array($current, $disabledTimes)) {
        $ticket_times[] = $current;
      }
      $unix_start = mktime(
        date('H', $unix_start),
        (date('i', $unix_start) + $period)
      );
    }

    return $ticket_times;
  }

  public function getDisabledTime($dateStart, $dateFinish, $checkDay = null, $ticketId = null)
  {
    $dateStart  = date('Y-m-d', strtotime($dateStart));
    $dateFinish = date('Y-m-d', strtotime($dateFinish));
    $cacheKey = $dateStart . '|' . $dateFinish;

    if (!isset($this->disabledTimeCache[$cacheKey])) {
      $data = ['days' => [], 'tickets' => []];
      foreach (
        Query::sqlQuery('SELECT * FROM ' . Query::tableName('tickets_disabled_time') . ' tdt 
			WHERE "' . $dateStart . '" <= tdt.disabled_date AND "' . $dateFinish . '" >= tdt.disabled_date order by tdt.disabled_date, tdt.disabled_time') as $row
      ) {
        $data['days'][$row['disabled_date']][$row['ticket_id']][]    = TimeHelper::convertTime24($row['disabled_time'], false);
        $data['tickets'][$row['ticket_id']][$row['disabled_date']][] = TimeHelper::convertTime24($row['disabled_time'], false);
      }
      $this->disabledTimeCache[$cacheKey] = $data;
    }
    $data = $this->disabledTimeCache[$cacheKey];

    $out = $data['days'] ?? [];

    if ($checkDay) {
      $out = $out[$checkDay] ?? [];
      if ($ticketId) {
        $out = $out[$ticketId] ?? [];
      }
    } else {
      if ($ticketId) {
        $out = $data['tickets'][$ticketId] ?? [];
      }
    }

    return $out;
  }

  /**
   * Сбрасывает кэш исключённых слотов после изменения данных.
   *
   * @return void
   */
  public function resetDisabledTimeCache(): void
  {
    $this->disabledTimeCache = [];
  }

  function checkLightTicket($type, $area_id, $date, $time_start, $weekday)
  {
    $query = 'SELECT t.ticket_id FROM ' . Query::tableName('tickets_light') . ' tl
					INNER JOIN ' . Query::tableName(
        'tickets'
      ) . ' t ON tl.ticket_id = t.ticket_id where t.area_id = "' . $area_id . '" AND tl.type="' . $type . '" AND tl.date="' . $date . '" AND tl.time_start="' . $time_start . '" AND tl.weekday="' . $weekday . '"';

    $temp = Query::sqlQuery($query);
    if (!empty($temp)) {
      return true;
    }

    return false;
  }


  //Выдача всех отдельных игр для абонемента со светом
  function getLightTicketData($date)
  {
    $query = 'SELECT tl.type, t.area_id, tl.weekday, tl.date, tl.time_start FROM ' . Query::tableName('tickets_light') . ' tl
				    INNER JOIN ' . Query::tableName('tickets') . ' t ON tl.ticket_id = t.ticket_id where tl.date="' . $date . '"';
    $temp  = Query::sqlQuery($query);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[$row['type']][$row['area_id']][$row['weekday']][] = date(
          'H:i',
          strtotime($row['date'] . ' ' . $row['time_start'])
        );
      }

      return $result;
    }

    return false;
  }

  //Взять все игры сос светом для абонемента
  function getLightHeatingTicketDataByTicketId($ticket_id)
  {
    $query = 'SELECT type, weekday, date, time_start, price FROM ' . Query::tableName('tickets_light') . ' where ticket_id="' . $ticket_id . '"';
    $temp  = Query::sqlQuery($query);
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $result[$row['type']][$row['date']][$row['weekday']][date(
          'H:i',
          strtotime($row['date'] . ' ' . $row['time_start'])
        )]
          = $row['price'];
      }

      return $result;
    }

    return false;
  }


  /* ПРОВЕРКА */

  //нет ли билета на площадку $area_id, дата $mysql_date, время $mysql_time и день недели $weekday
  //mysql_date - yyyy-mm-dd
  //mysql_time - hh:mm
  //weekday - 0 = пн
  function checkAreaDateTimeReserved($area_id, $mysql_date, $mysql_time, $weekday)
  {
    $q    = 'SELECT t.*, tp.*, 
       if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period, 
       if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin  
			FROM ' . Query::tableName('tickets_periods') . ' tp,
				' . Query::tableName('tickets') . ' t 
			WHERE t.ticket_id = tp.ticket_id 
			  and t.area_id = ' . $area_id . ' 
			  and find_in_set(' . $weekday . ', t.weekdays) 
			  and "' . $mysql_date . '" <= tp.finish 
			  and "' . $mysql_date . '" >=tp.start 
			  and "' . $mysql_time . ':00" >= t.time_start 
			  and "' . $mysql_time . ':00" < t.time_finish
        and (select disabled_time_id
       from ' . Query::tableName('tickets_disabled_time') . '
       where ticket_id = t.ticket_id
         and "' . $mysql_date . '" = disabled_date
         and "' . $mysql_time . ':00" = disabled_time
        ) is null';
    $temp = Query::sqlQuery($q);
    //проверка на пересечение с другими билетами с учетом двух/одно недельных абонементов
    if (!empty($temp)) {
      foreach ($temp as $row) {
        $old_work_days[] = $this->getDateEveryGameInTicket(
          strtotime($row['start']),
          strtotime($row['finish']),
          explode(',', $row['weekdays']),
          $row['space'],
          CalendarHelper::getFirstDayTicket($row['min_start_period'], explode(',', $row['weekdays']), $row['week_begin'])
        );
      }
      foreach ($old_work_days as $wd) {
        if (in_array($mysql_date, $wd)) {
          return true;
        }
      }
    }

    return false;
  }

  public function getAbosDataOverPeriod(
    $area_id,
    $date_start,
    $date_finish,
    $time_start,
    $time_finish,
    $weekdays
  ) {
    $days = DateHelper::getWorkingDaysForPeriod($date_start, $date_finish, $weekdays);
    $out  = [];


    $query = 'SELECT t.*, tp.*, tp.start as date_start, tp.finish as date_finish, concat(c.surname, \' \',c.name) as name
			FROM ' . Query::tableName('tickets_periods') . ' tp,
				' . Query::tableName('tickets') . ' t
			left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id 
 			WHERE t.ticket_id = tp.ticket_id and
				t.area_id = ' . $area_id . ' AND (
			      ("' . $date_finish . '">=tp.start and
			      "' . $date_start . '"<=tp.finish)) and t.time_start<="' . $time_finish . ':00" and t.time_finish>="' . $time_start . ':00"';
    //проверяем периодические блокировки
    foreach (Query::sqlQuery($query) as $row) {
      $gameDays = $this->getDateEveryGameInTicket(
        strtotime($row['start']),
        strtotime($row['finish']),
        explode(',', $row['weekdays']),
        $row['space'],
        CalendarHelper::getFirstDayTicket(
          $row['date_start'] ?: $date_start,
          $weekdays,
          $row['week_begin']
        )
      );
      foreach ($gameDays as $gameDay) {
        if (in_array($gameDay, $days)) {
          $row['useDays'][] = $gameDay;
        }
      }
      $row['type'] = "Abo";
      if (isset($row['useDays']) && is_array($row['useDays']) && count($row['useDays']) > 0) {
        $out[] = $row;
      }
    }

    return $out;
  }

  public function addPeriodToTicketFit($ticket_id, $start)
  {
    return Query::sqlQuery('INSERT INTO ' . Query::tableName('tickets_periods') . ' (ticket_id, start, finish) VALUES ' .
      '(' . $ticket_id . ', "' . $start . '", null )', [], false);
  }

  public function addFinishDateFitnessAbo($ticket_id, $finish)
  {
    return Query::sqlQuery('update ' . Query::tableName('tickets_periods') . ' set finish = ' . ($finish !== null ? '"' . date('Y-m-d',
          strtotime($finish)) . '"' : "null") . ' where ticket_id=' . $ticket_id, [], false);
  }
}
