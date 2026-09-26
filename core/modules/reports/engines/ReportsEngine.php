<?php

namespace AC\core\modules\reports\engines;

use AC\app\entities\enums\Encash;
use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\clients\entities\dto\ClientDto;
use AC\core\modules\clients\helpers\ClientHelper;
use AC\core\modules\config\entities\dto\ExtraDto;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\JsonHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use DateTimeImmutable;
use Service;

/**
 * Подготовка данных административных отчётов.
 */
class ReportsEngine
{
  public function getPayOneReports($mysql_start_date, $mysql_end_date, $typeBySport = null)
  {
    $type_id = $sport_id = null;
    if ($typeBySport) {
      [$type_id, $sport_id] = explode('_', $typeBySport);
    }
    $mysql_start_date = date('Y-m-d 00:00:00', strtotime($mysql_start_date));
    $mysql_end_date   = date('Y-m-d 00:00:00', strtotime($mysql_end_date) + 86400);
    
    $hasRealReservations = Service::query()::getDB()->checkField('real_reservation_id', 'reservations_tmp_paypal');
    $hasPayOnlineType    = Service::query()::getDB()->checkField('pay_one_type', 'reservations_tmp_paypal');
    $hasReference        = Service::query()::getDB()->checkField('reference', 'reservations_tmp_paypal');
    $hasPaypalStatus     = Service::query()::getDB()->checkField('paypal_status', 'reservations_tmp_paypal');

    $areas = DataService::areas();
    //обычные резервирования
    $q    =
      'SELECT
						rtp.client_id,
						COALESCE(c.name, rtp.client_name) AS name,
						COALESCE(c.surname, rtp.client_surname)  AS surname,
						COALESCE(c.email, \'\') AS email,
						COALESCE(c.registered, \'\') AS registered,
						rtp.area_id,
            rtp.memo AS comment,
						rtp.start,
						rtp.finish,
						rtp.price,
            rtp.light_price AS light_price,
            rtp.heating_price AS heating_price,
            rtp.net_price AS net_price,
            rtp.pay_state,
            rtp.ordered' .
      ($hasPayOnlineType ? ', rtp.pay_one_type' : '') .
      ($hasPaypalStatus ? ', rtp.paypal_status' : '') .
      ($hasReference ? ', rtp.reference' : '') .
      ($hasRealReservations ? ', res.reservation_id AS real_reservation_id' : '') .
      ' FROM ' . Query::tableName('reservations_tmp_paypal') . ' rtp
					INNER JOIN ' . Query::tableName('areas') . ' a ON a.area_id = rtp.area_id
					INNER JOIN ' . Query::tableName('clients') . ' c ON rtp.client_id = c.client_id ' .
      ($hasRealReservations ? 'LEFT JOIN ' . Query::tableName('reservations') . ' res ON res.reservation_id = rtp.real_reservation_id' : '') .
      ' WHERE rtp.ordered BETWEEN "' . $mysql_start_date . '" AND "' . $mysql_end_date . '" AND rtp.encash="3" AND rtp.pay_state IS NOT NULL'
      . ((isset($type_id) && $type_id != 0) ? ' AND a.type_id = ' . (int)$type_id : '')
      . ((isset($sport_id) && $sport_id != 0) ? ' AND a.sport_id = ' . (int)$sport_id : '')
      . ' ORDER BY c.surname, c.name, rtp.ordered, a.area_id';
    $data = [];
    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        $ordered                 = new DateTimeImmutable($row['ordered']);
        $start                   = new DateTimeImmutable($row['start']);
        $finish                  = new DateTimeImmutable($row['finish']);
        $data[$row['ordered']][] = [
          'ordered_date' => $ordered->format('d.m.Y'),
          'ordered_time' => $ordered->format('H:i'),
          'pay_state'    => $row['pay_state'],
          'reference'    => explode('|', $row['reference'])[0] ?? '',
          'surname'      => $row['surname'],
          'name'         => $row['name'],
          'email'        => $row['email'],
          'registered'   => $row['registered'],
          'area_type'    => $areas[$row['area_id']]->getTitleForTypeSport(),
          'area'         => $areas[$row['area_id']]->getTitle(),
          'date'         => $start->format('d.m.Y'),
          'time'         => $start->format('H:i') . ' - ' . $finish->format('H:i'),
          'pay_one_type' => $row['pay_one_type'] ?? '',
          'paypal_status'=> $row['paypal_status'] ?? '',
          'gh_number'    => '',
          'instruction'  => $hasRealReservations ? (lang('instruction_' . ($row['real_reservation_id'] ? 'booking' : 'canceled'), 'reports')) : '',
          'price'        => (float)$row['price'] + (float)$row['light_price'] + (float)$row['heating_price'] + (float)$row['net_price'],
        ];
      }
    }
    // пополнение гутхабен через payone
    
    $q =
      'select ac.account_id, concat("GH00", ac.number) AS number, ac.date_start AS ordered, acac.surname, acac.name, acac.email, c.registered, acarp.price, ac.price_info from ' . Query::tableName('accounts') . ' ac
                INNER JOIN ' . Query::tableName('accounts_clients') . ' acac ON ac.client_id = acac.client_id
                INNER JOIN ' . Query::tableName('clients') . ' c ON c.client_id = acac.client_id
                INNER JOIN ' . Query::tableName('accounts_reservations_prepayment') . ' acarp ON ac.account_id = acarp.account_id
                WHERE ac.date_start BETWEEN "' . $mysql_start_date . '" AND "' . $mysql_end_date . '"
                AND ac.account_type = "3" AND ac.price_info like "paypal%" order by acac.surname, acac.name, ac.date_start';
    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        $ordered = new DateTimeImmutable($row['ordered']);
        
        $data[$row['ordered']][] = [
          'ordered_date' => $ordered->format('d.m.Y'),
          'ordered_time' => $ordered->format('H:i'),
          'pay_state'    => 'OK',
          'reference'    => explode('|', $row['price_info'])[1] ?? '',
          'surname'      => $row['surname'],
          'name'         => $row['name'],
          'email'        => $row['email'],
          'registered'   => $row['registered'],
          'area_type'    => '',
          'area'         => '',
          'date'         => '',
          'time'         => '',
          'pay_one_type' => '',
          'paypal_status'=> OnlineGatewayService::parseTypeKeyFromPrepaymentPriceInfo($row['price_info'] ?? '') ?? '',
          'gh_number'    => $row['number'],
          'instruction'  => lang('instruction_top_up_balance', 'reports'),
          'price'        => (float)$row['price'],
        ];
      }
    }
    ksort($data);
    return $data;
  }
  
  public function getOrders($mysql_start_date, $mysql_end_date)
  {
    $query = 'select
						if(rtp.client_id IS NULL,md5(concat(rtp.client_name,rtp.client_surname)),c.client_id) AS client_id,
						if(rtp.client_id IS NULL,rtp.client_name,c.name) AS name,
						if(rtp.client_id IS NULL,rtp.client_surname,c.surname)  AS surname,
						if(rtp.client_id IS NULL, \'\',c.email) AS email,
						if(rtp.client_id IS NULL,\'\',c.registered) AS registered,
						a.type_id,
						at.title AS type,
						a.area_id,
						a.title AS area,
            rtp.memo,
						rtp.start, substring(rtp.finish,12,5) AS finish,
						rtp.price,
						if(rtp.light_state="1", rtp.light_price, 0.00) AS light_price,
						if(rtp.heating_state="1", rtp.heating_price, 0.00) AS heating_price,
            rtp.pay_state, rtp.ordered, rtp.pay_one_type, rtp.paypal_status,
            if((select reservation_id from ' . Query::tableName('reservations') . ' where reservation_id = rtp.real_reservation_id) IS NULL, 0 , 1) AS completed
					from ' . Query::tableName('reservations_tmp_paypal') . ' rtp
					left join ' . Query::tableName('areas') . ' a ON a.area_id = rtp.area_id
					left join ' . Query::tableName('areas_types') . ' at ON at.type_id = a.type_id
					left join ' . Query::tableName('clients') . ' c ON rtp.client_id = c.client_id
					where ("' . $mysql_start_date . '" <= substring(rtp.ordered,1,10) AND "' . $mysql_end_date . '" >= substring(rtp.ordered,1,10)) AND rtp.encash="3" AND rtp.pay_state is not null'
      . ((isset($type_id) && $type_id != 0) ? ' AND a.type_id = ' . $type_id : '')
      . ' ORDER BY c.surname, c.name, rtp.ordered, a.area_id';
    
    return Query::sqlQuery($query);
  }
  
  //данные о резервировани по заданной площадке и unixtime
  function getReservationData($area_id, $mysql_datetime, $tmp = false)
  {
    $query = 'select
						if(rtp.client_id IS NULL,md5(concat(rtp.client_name,rtp.client_surname)),c.client_id) AS client_id,
						if(rtp.client_id IS NULL,rtp.client_name,c.name) AS name,
						if(rtp.client_id IS NULL,rtp.client_surname,c.surname)  AS surname,
						if(rtp.client_id IS NULL, \'\',c.email) AS email,
						if(rtp.client_id IS NULL,\'\',c.registered) AS registered,
						a.type_id,
						at.title AS type,
						a.area_id,
						a.title AS area,
            rtp.memo,
						rtp.start, substring(rtp.finish,12,5) AS finish,
						rtp.price,
						if(rtp.light_state="1", rtp.light_price, 0.00) AS light_price,
						if(rtp.heating_state="1", rtp.heating_price, 0.00) AS heating_price,
            rtp.*
					from ' . Query::tableName('reservations' . ($tmp ? '_tmp_paypal' : '')) . ' AS rtp
					left join ' . Query::tableName('areas') . ' a ON a.area_id = rtp.area_id
					left join ' . Query::tableName('areas_types') . ' at ON at.type_id = a.type_id
					left join ' . Query::tableName('clients') . ' c ON rtp.client_id = c.client_id
					where rtp.area_id = ' . $area_id . ' AND rtp.start = "' . $mysql_datetime . '"'
      . ((isset($type_id) && $type_id != 0) ? ' AND a.type_id = ' . $type_id : '')
      . ' ORDER BY c.surname, c.name, rtp.ordered, a.area_id';
    
    return Query::sqlQuery($query);
  }
  
  /**
   * Добавить бронирования площадок, доступных в текущем справочнике.
   */
  public function getReservations(string $mysql_start_date, string $mysql_end_date, ?string $typeBySport = null, array &$result = []): void
  {
    $type_id = $sport_id = null;
    if ($typeBySport) {
      [$type_id, $sport_id] = explode('_', $typeBySport);
    }
    $mysql_start_date = date('Y-m-d H:i:s', strtotime($mysql_start_date));
    $mysql_end_date   = date('Y-m-d H:i:s', strtotime($mysql_end_date));
    $areas            = DataService::areas();
    $nds              = DataService::nds(['addDefault' => true]);
    $discounts        = DataService::discounts();
    $q                = '
      WITH
      CustomFields AS (
          SELECT
              rd.reservation_id,
              JSON_ARRAYAGG(
                  JSON_OBJECT(\'name\', rd.name, \'value\', rd.value, \'entry_id\', rd.entry_id, \'type_block\', rd.type_block)
              ) AS custom_fields
          FROM ' . Query::tableName('reservation_data') . ' rd
          WHERE rd.name IS NOT NULL
          GROUP BY rd.reservation_id
      ),
      OtherPeriods AS (
          SELECT
              r.main_reservation_id,
              JSON_ARRAYAGG(
                  JSON_OBJECT(
                      \'reservation_id\', r.reservation_id,
                      \'start\', r.start,
                      \'finish\', r.finish,
                      \'price\', r.price,
                      \'light_price\', r.light_price,
                      \'heating_price\', r.heating_price,
                      \'net_price\', r.net_price
                  )
              ) AS other_periods
          FROM ' . Query::tableName('reservations') . ' r
          WHERE r.main_reservation_id IS NOT NULL
          GROUP BY r.main_reservation_id
      )
    SELECT
      r.reservation_id,
      r.area_id,
      r.type_reservation,
      r.status,
      r.payment_state,
      r.stock_id,
      r.sprice_id,
      r.memo AS comment,
      r.start,
      r.finish,
      r.price,
      r.light_price AS light_price,
      r.heating_price AS heating_price,
      r.net_price AS net_price,
      r.encash,
      r.street_friends,
      r.main_client_id,
      r.main_reservation_id,
      r.client_id,
      IF(r.client_id IS NULL, r.client_name, c.name) AS client_name,
      IF(r.client_id IS NULL, r.client_surname, c.surname) AS client_surname,
      IF(c.mode IS NULL, 3, c.mode) AS client_mode,
      c.club_state AS client_club_state,
      c.nds AS client_nds,
      c.discount AS client_discount_id,
      COALESCE(cf.custom_fields, JSON_ARRAY()) AS custom_fields,
      COALESCE(op.other_periods, JSON_ARRAY()) AS other_periods
  FROM ' . Query::tableName('reservations') . ' r
  INNER JOIN ' . Query::tableName('areas') . ' a ON a.area_id = r.area_id
  LEFT JOIN ' . Query::tableName('clients') . ' c ON r.client_id = c.client_id
  LEFT JOIN CustomFields cf ON cf.reservation_id = r.reservation_id
  LEFT JOIN OtherPeriods op ON op.main_reservation_id = r.reservation_id
  WHERE r.main_reservation_id IS NULL AND r.start BETWEEN "' . $mysql_start_date . '" AND "' . $mysql_end_date . '"'
      . ((isset($type_id) && $type_id != 0) ? ' AND a.type_id = ' . (int)$type_id : '')
      . ((isset($sport_id) && $sport_id != 0) ? ' AND a.sport_id = ' . (int)$sport_id : '')
      . ' GROUP BY r.reservation_id, r.client_id, a.area_id, r.start'
      . ' ORDER BY a.area_id, r.start, r.reservation_id;';
    if ($temp = Query::sqlQuery($q)) {
      foreach ($temp as $row) {
        // SQL может вернуть историю неактивной или архивной площадки, отсутствующей в справочнике.
        if (!isset($areas[$row['area_id']])) {
          continue;
        }
        foreach (JsonHelper::decode($row['custom_fields'], true) as $item) {
          if (!empty($item) && !empty($item['name']) && !empty($item['type_block']) && !empty($item['entry_id'])) {
            $row[$item['type_block']][$item['entry_id']][$item['name']] = $item['value'];
          }
        }
        unset($row['custom_fields']);
        $times  = [date('H:i', strtotime($row['start']))];
        $prices = [
          'price'         => $row['price'],
          'light_price'   => $row['light_price'],
          'heating_price' => $row['heating_price'],
          'net_price'     => $row['net_price'],
        ];
        if (!empty($row['other_periods'])) {
          foreach (JsonHelper::decode($row['other_periods'], true) as $other_period) {
            $times[]                 = date('H:i', strtotime($other_period['start']));
            $prices['price']         += $other_period['price'];
            $prices['light_price']   += $other_period['light_price'];
            $prices['heating_price'] += $other_period['heating_price'];
            $prices['net_price']     += $other_period['net_price'];
          }
        }
        foreach (TimeHelper::getTimeByStartFinish($times, $areas[$row['area_id']]->period) as $timeTitle) {
          $result[] = [
            'typeEvent'       => EventMode::Reservation,
            'area'            => $areas[$row['area_id']],
            'date'            => date('Y-m-d', strtotime($row['start'])),
            'time'            => $timeTitle,
            'client_id'       => $row['client_id'] ?? md5($row['client_name'] . $row['client_surname']),
            'client_name'     => ClientHelper::generatePlayerName($row['client_name'], $row['client_surname']),
            'client_mode'     => $row['client_mode'],
            'encash'          => Encash::from($row['encash']),
            'prices'          => $prices,
            'comment'         => $row['comment'],
            'stocks'          => !empty($row['stocks']) ? array_keys($row['stocks']) : (!empty($row['stock_id']) ? [$row['stock_id']] : []),
            'specPrices'      => !empty($row['specPrices']) ? array_keys($row['specPrices']) : (!empty($row['sprice_id']) ? [$row['sprice_id']] : []),
            'club_state'      => $row['client_club_state'] ? DataService::clubStateModel()::getItemById($row['client_club_state'],
              'title') : 'PP ' . lang('Guest'),
            'client_nds'      => $nds[$row['client_nds']] ?? $nds['default'],
            'client_discount' => $discounts[$row['client_discount_id']] ?? null,
            'ticket_discount' => null,
            'count_periods'   => count($times),
            'street_friends'  => !empty($row['street_friends']) ? Service::cast('array')->get($row['street_friends']) : [],
            'payment_status'  => (int)$row['payment_state'],
            'status'          => (int)$row['status'],
            'times'           => $times,
          ];
        }
      }
    }
  }
  
  /**
   * Добавить занятия абонементов площадок, доступных в текущем справочнике.
   */
  public function getSeasonTickets(string $mysql_start_date, string $mysql_end_date, ?string $typeBySport = null, array &$result = []): void
  {
    $type_id = $sport_id = null;
    if ($typeBySport) {
      [$type_id, $sport_id] = explode('_', $typeBySport);
    }
    $mysql_start_date = date('Y-m-d', strtotime($mysql_start_date));
    $mysql_end_date   = date('Y-m-d', strtotime($mysql_end_date));
    $areas            = DataService::areas();
    $nds              = DataService::nds(['addDefault' => true]);
    $discounts        = DataService::discounts();
    $q                = 'SELECT
            t.ticket_id,
            t.time_start,
            t.time_finish,
            t.discount_id AS ticket_discount_id,
            t.weekdays,
            t.space,
            t.title AS comment,
            COALESCE(t.period_start, ms1.first_start) AS min_start_period,
            COALESCE(t.week_begin, ms1.first_start_week) AS week_begin,
            COALESCE(JSON_ARRAYAGG( JSON_OBJECT(\'ticket_id\', tp.ticket_id, \'period_id\', tp.period_id, \'start\', tp.start, \'finish\', tp.finish)), JSON_ARRAY()) AS periods_data,
            a.area_id,
            c.client_id,
            c.mode AS client_mode,
            c.name AS client_name,
            c.surname AS client_surname,
            c.club_state AS client_club_state,
            c.nds AS client_nds,
            c.discount AS client_discount_id,
            COALESCE((SELECT JSON_ARRAYAGG( JSON_OBJECT(
              \'ticket_id\', ticket_id, \'period_id\', period_id, \'time\', disabled_time, \'date\', disabled_date))
              FROM ' . Query::tableName('tickets_disabled_time') . ' WHERE ticket_id = t.ticket_id
              AND "' . $mysql_start_date . '" <= disabled_date AND "' . $mysql_end_date . '" >= disabled_date), JSON_ARRAY()) AS disabled_time
        FROM ' . Query::tableName('tickets') . ' t
        INNER JOIN ' . Query::tableName('tickets_periods') . ' tp ON t.ticket_id = tp.ticket_id
        INNER JOIN ' . Query::tableName('areas') . ' a ON a.area_id = t.area_id
        INNER JOIN ' . Query::tableName('clients') . ' c ON t.client_id = c.client_id
        LEFT JOIN (SELECT ticket_id, MIN(`start`) AS first_start, WEEK(MIN(`start`), 3) AS first_start_week
                    FROM ' . Query::tableName('tickets_periods') . '
                    GROUP BY ticket_id) ms1 ON ms1.ticket_id = t.ticket_id
        WHERE (("' . $mysql_start_date . '" >= tp.start AND "' . $mysql_start_date . '" <= tp.finish)
            OR ("' . $mysql_end_date . '" >= tp.start AND "' . $mysql_end_date . '" <= tp.finish)
            OR ("' . $mysql_start_date . '" <= tp.start AND "' . $mysql_end_date . '" >= tp.finish))'
      . ((isset($type_id) && $type_id != 0) ? ' AND a.type_id = ' . (int)$type_id : '')
      . ((isset($sport_id) && $sport_id != 0) ? ' AND a.sport_id = ' . (int)$sport_id : '') .
      ' GROUP BY t.ticket_id ORDER BY t.ticket_id, tp.start;';
    if ($rows = Query::sqlQuery($q)) {
      foreach ($rows as $row) {
        // SQL может вернуть историю неактивной или архивной площадки, отсутствующей в справочнике.
        if (!isset($areas[$row['area_id']])) {
          continue;
        }
        $area = $areas[$row['area_id']];
        /** @var ExtraDto $extra */
        $periods      = JsonHelper::decode($row['periods_data'], true);
        $first_days   = CalendarHelper::getFirstDayTicket($row['min_start_period'], $row['weekdays'], $row['week_begin'],
          false);
        $workTimes    = array_keys(Service::engines()->tickets->renderTicketTime($row['time_start'], $row['time_finish'],
          $area->period, []));
        $disabledTime = [];
        foreach (JsonHelper::decode($row['disabled_time'], true) as $desTime) {
          $disabledTime[$desTime['date']][] = TimeHelper::convertTime24($desTime['time'], false);;
        }
        $weekdays = explode(',', $row['weekdays']);
        $workDays = CalendarHelper::getWorkDaysByPeriod($mysql_start_date, $mysql_end_date, $weekdays, $first_days, (int)$row['space'], $periods,
          false);
        foreach (array_keys($workDays) as $day) {
          $timesDay = [];
          foreach ($workTimes as $time) {
            if (!in_array($time, $disabledTime[$day] ?? [])) {
              $timesDay[] = $time;
            }
          }
          foreach (TimeHelper::getTimeByStartFinish($timesDay, $area->period, false, true) as $timeTitle => $times) {
            $prices = [
              'price'         => 0,
              'light_price'   => 0,
              'heating_price' => 0,
              'net_price'     => 0,
            ];
            foreach ($times as $time) {
              $prices['price'] += Service::engines()->tickets->getPriceTicketByDateTime($day, $time, $row);
              if (config('reports')->addWebIoTypesPrice()) {
                $prices['light_price']   += $area->getStatePrice('light');
                $prices['heating_price'] += $area->getStatePrice('heating');
                $prices['net_price']     += $area->getStatePrice('net');
              }
            }
            $result[] = [
              'typeEvent'       => EventMode::Ticket,
              'area'            => $area,
              'date'            => $day,
              'time'            => $timeTitle,
              'client_id'       => $row['client_id'] ?? md5($row['client_name'] . $row['client_surname']),
              'client_name'     => ClientHelper::generatePlayerName($row['client_name'], $row['client_surname']),
              'client_mode'     => $row['client_mode'],
              'encash'          => Encash::Invoice,
              'prices'          => $prices,
              'comment'         => $row['comment'],
              'stocks'          => [],
              'specPrices'      => [],
              'club_state'      => DataService::clubStateModel()::getItemById($row['client_club_state'], 'title'),
              'client_nds'      => $nds[$row['client_nds']] ?? $nds['default'],
              'client_discount' => $discounts[$row['client_discount_id']] ?? null,
              'ticket_discount' => $discounts[$row['ticket_discount_id']] ?? null,
              'count_periods'   => count($times),
              'street_friends'  => [],
              'payment_status'  => 1,
              'status'          => 1,
              'times'           => $times,
            ];
          }
        }
      }
    }
  }
  
  public function getBlocks($mysql_start_date, $mysql_end_date, $area_id = null): array
  {
    $mysql_start_date = date('Y-m-d', strtotime($mysql_start_date));
    $mysql_end_date   = date('Y-m-d', strtotime($mysql_end_date));
    
    return Service::engines()->blocks->checkAreaDateTimePeriodsBlocked($mysql_start_date, $mysql_end_date, $area_id);
  }
  
  public function getHolidays($mysql_start_date, $mysql_end_date, $asSunday = false): array
  {
    $mysql_start_date = date('Y-m-d', strtotime($mysql_start_date));
    $mysql_end_date   = date('Y-m-d', strtotime($mysql_end_date));
    
    return Service::engines()->holidays->getHolidaysByDateInterval($mysql_start_date, $mysql_end_date, $asSunday);
  }
  
  public function getPrivateAccountData($mysql_start_date, $mysql_end_date): array
  {
    $data             = [];
    $mysql_start_date = date('Y-m-d', strtotime($mysql_start_date)) . ' 00:00:00';
    $mysql_end_date   = date('Y-m-d', strtotime($mysql_end_date . ' +1 day')) . ' 00:00:00';
    $clients          = [];
    if ($temp = Query::sqlQuery('SELECT client_id, login, name, surname, prepayment_sum FROM ' . Query::tableName('clients') . ' where prepayment_sum > 0')) {
      foreach ($temp as $row) {
        $row['income']              = 0;
        $row['outcome']             = 0;
        $clientName                 = ClientHelper::generatePlayerName($row['name'], $row['surname']) . '|' . $row['client_id'];
        $clients[$row['client_id']] ??= $clientName;
        $data[$clientName]          = $row;
      }
      unset($temp);
    }
    $transactions     = ModCommHelper::get('clients', 'privateAccount/transactions',
      ['date_start' => $mysql_start_date, 'date_finish' => $mysql_end_date, 'typeDirection' => 'full'], 'transactions');
    $firstTransaction = $transactions['first'] ?? null;
    if (!$firstTransaction || $firstTransaction > $mysql_start_date) {
      $mysql_end_date = $firstTransaction < $mysql_end_date ? $firstTransaction : $mysql_end_date;
      $temp = Query::sqlQuery(
        'SELECT cl.client_id, cl.login, cl.name, cl.surname, arp.price, cl.prepayment_sum
			FROM ' . Query::tableName('clients') . ' cl
			left join ' . Query::tableName('accounts_clients') . ' acc ON acc.sys_client_id = cl.client_id
			left join ' . Query::tableName('accounts') . ' a ON a.client_id = acc.client_id
			left join ' . Query::tableName('accounts_reservations_prepayment') . ' arp ON arp.account_id = a.account_id
			WHERE a.account_type="3" AND a.execution="1" AND a.deleted="0" AND  (a.date_start BETWEEN "' . $mysql_start_date . '" AND "' . $mysql_end_date . '")
			GROUP BY a.account_id ORDER BY cl.surname, cl.name');
      if ($temp) {
        foreach ($temp as $row) {
          $row['income']               = 0;
          $row['outcome']              = 0;
          $clientName                  = ClientHelper::generatePlayerName($row['name'], $row['surname']) . '|' . $row['client_id'];
          $clients[$row['client_id']]  ??= $clientName;
          $data[$clientName]           ??= $row;
          $data[$clientName]['income'] += $row['price'];
        }
      }
      unset($temp);
      $temp = Query::sqlQuery(
        'SELECT cl.client_id,cl.login, cl.name, cl.surname, SUM(r.price) AS outcome, cl.prepayment_sum
                FROM ' . Query::tableName('clients') . ' cl
									LEFT JOIN ' . Query::tableName('reservations') . ' r ON r.client_id = cl.`client_id`
									WHERE (r.start BETWEEN "' . $mysql_start_date . '" AND "' . $mysql_end_date . '") AND r.encash = "2"
									GROUP BY cl.client_id ORDER BY cl.surname, cl.name'
      );
      if ($temp) {
        foreach ($temp as $row) {
          $row['income']                = 0;
          $clientName                   = ClientHelper::generatePlayerName($row['name'], $row['surname']) . '|' . $row['client_id'];
          $clients[$row['client_id']]   ??= $clientName;
          $data[$clientName]            ??= $row;
          $data[$clientName]['outcome'] += $row['outcome'];
        }
      }
    }
    foreach ($transactions['all'] as $transaction) {
      if (isset($clients[$transaction->client_id])) {
        $clientName = $clients[$transaction->client_id];
      } else {
        /** @var ClientDto $clientDto */
        $clientDto                      = ModCommHelper::get('clients', 'getClientDataById', ['client_id' => $transaction->client_id], 'client');
        $clientName                     = ClientHelper::generatePlayerName($clientDto->name, $clientDto->surname) . '|' . $transaction->client_id;
        $clients[$clientDto->client_id] ??= $clientName;
        $data[$clientName]              ??= [
          'client_id'      => $transaction->client_id,
          'login'          => $clientDto->login,
          'name'           => $clientDto->name,
          'surname'        => $clientDto->surname,
          'outcome'        => 0,
          'income'         => 0,
          'prepayment_sum' => $clientDto->prepayment_sum,
        ];
      }
      $data[$clientName]['income']                              ??= 0;
      $data[$clientName]['outcome']                             ??= 0;
      $data[$clientName][$transaction->type_direction . 'come'] += $transaction->amount;
    }
    ksort($data);
    return $data;
  }
}
