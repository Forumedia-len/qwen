<?php
//error_reporting (E_ALL);

use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

$out         = '';
$start_year  = Service::request()->_('start_year');
$start_month = Service::request()->_('start_month');
$start_day   = Service::request()->_('start_day');
$end_year    = Service::request()->_('end_year');
$end_month   = Service::request()->_('end_month');
$end_day     = Service::request()->_('end_day');
if ($type = Service::request()->_('type')) {
  list($type_id, $sport_id) = explode('_', $type);
}
$periods = $clients = [];
$engine  = Service::engines();

$tdata = [];
$pdata = [];

$stocks = ModCommHelper::get('stocks', 'stocks/getStocks', ['as' => 'dto'], 'stocks', []);

if ($start_year !== null && $start_month !== null && $start_day !== null &&
  $end_year !== null && $end_month !== null && $end_day !== null) {
  $mysql_start_date = "$start_year-$start_month-$start_day";
  $start_unixtime   = strtotime("$start_year-$start_month-$start_day ");
  $all_clients      = $engine->clients->getClientsByAreaType(array(0, $type_id));
  if ($type_id) {
    $temp1 = Query::sqlQuery(
      'select title from ' . Query::tableName('areas_types') . ($type_id ? ' where type_id =' . (int)$type_id : ''),
      [],
      true,
      ['style' => PDO::FETCH_NUM]
    );
    if (!empty($temp1)) {
      list ($type_title) = $temp1[0][0];
    }
    if ($sport_id) {
      $sport_title = $engine->sports->getSportsTitlesByType($type_id, $sport_id, false);
    }
  }
  $mysql_end_date = "$end_year-$end_month-$end_day";
  $end_unixtime   = strtotime("$end_year-$end_month-$end_day ");

  if (checkdate($start_month, $start_day, $start_year) && checkdate(
      $end_month,
      $end_day,
      $end_year
    ) && $mysql_end_date >= $mysql_start_date) {
//обычные резервирования
    $temp = $engine->renderReservationData(Query::sqlQuery(
      'select
          r.reservation_id,
          if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id) as client_id,
          if(r.client_id is null,r.client_name,c.name) as client_name,
          if(r.client_id is null,r.client_surname,c.surname)  as client_surname,
          if(r.main_client_id is null,"",cm.name) as main_name,
          if(r.main_client_id is null,"",cm.surname) as main_surname,
          at.type_id,
          at.title as type,
          asp.title as sport,
          a.area_id,
          a.title as area,
          r.stock_id, sp.code as sprice, r.memo,
          r.start, substring(r.finish,12,5) as finish,
          r.price,
          if(r.light_state="1", r.light_price, 0.00) as light_price,
          if(r.heating_state="1", r.heating_price, 0.00) as heating_price,
          r.encash,
          if(c.mode is null,2,c.mode - 1) as mode,
          r.type_reservation, r.street_friends, r.main_client_id,
          cs.title as club_state  
         , rd.name, rd.value, rd.entry_id, rd.type_block
         from ' . Query::tableName('reservations') . ' r
          left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
          left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
          left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
          left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
          left join ' . Query::tableName('clients') . ' cm on r.main_client_id = cm.client_id 
          left join ' . Query::tableName('reservations_specprice') . ' sp on r.sprice_id = sp.sprice_id
          LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON rd.reservation_id = r.reservation_id
          left join ' . DB_TABLE_PREFIX . 'config_club_state cs on cs.id = c.club_state
          where "' . $mysql_start_date . '" <= substring(r.start,1,10) and "' . $mysql_end_date . '" >= substring(r.start,1,10)' .
      ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '') .
      ' order by at.type_id, asp.sport_id, a.area_id, r.reservation_id'
    ));
    foreach ($temp as $r) {
      $stocksCode = [];
      $stocksIds  = isset($r['stocks']) ? array_keys($r['stocks']) : [$r['stock_id']];
      foreach ($stocksIds as $stockId) {
        if (isset($stocks[$stockId])) {
          $stocksCode[] = $stocks[$stockId]->code;
        }
      }
      if (!isset ($clients[$r['client_id']])) {
        $clients[$r['client_id']] = array(($r['client_surname'] ? $r['client_surname'] . ' ' : '') . $r['client_name'], $r['mode'],);
      }
      $list_friends = '';
      if (!empty($r['street_friends'])) {
        $friends = unserialize($r['street_friends'], ['allowed_classes' => false]);

        foreach ($friends as $friend) {
          if($friend) {
            $list_friends .= ', <span style="white-space:nowrap">' . $friend['name'] . ' (' . ConfigClubStateModel::getItemById($friend['club_state'],
                'short') . ')</span>';
          }
        }
        $list_friends = trim($list_friends, ', ');
      }

      if (empty($r['main_client_id'])) {
        $last1 = $r['type'];
        $last2 = $r['sport'];
        $last3 = substr($r['start'], 0, 10);
        $last4 = $r['area_id'] . '#' . substr($r['start'], 11);
        $periods[$r['type']][$r['sport']][substr($r['start'], 0, 10)][$r['area_id'] . '#' . substr(
          $r['start'],
          11
        )]
               = array(
          $r['area']
          . (empty($stocksCode) ? '' : ' <strong>[</strong>' . implode(' | ', $stocksCode). '<strong>]</strong>')
          . (empty($r['sprice']) ? '' : ' <strong>[' . $r['sprice'] . ']</strong>')
            . ($r['encash'] == 2
            ? ' <strong>GH</strong>'
            : ($r['encash'] == 3
              ? '<img src="' . base_url(
                paths()->getAssetsDir('images/buttons/paypal_small.png')) . '" alt="paypal" style="margin-bottom:-3px;"/>' : '')) . (empty($r['memo'])
            ? ''
            : ' <br/>[' . $r['memo'] . ']'),
          TimeHelper::convertTime24($r['finish'], false),
          $r['client_id'],
          $r['price'],
          ($r['encash'] == 1 && is_numeric($r['client_id']) ? 1 : $r['encash']),
          $r['light_price'],
          $r['heating_price'],
          'reservation',
          'club_state' => ConfigClubStateModel::getItemById($all_clients[$r['client_id']]->club_state, 'title') ?? 'PP ' . lang('Guest'),
          'friends'    => $list_friends
        );
      } else {
        $periods[$last1][$last2][$last3][$last4][1] = TimeHelper::convertTime24($r['finish'], false);
        $periods[$last1][$last2][$last3][$last4][0] = $r['area']
          . (empty($stocksCode) ? '' : ' <strong>[</strong>' . implode(' | ', $stocksCode) . '<strong>]</strong>')
          . (empty($r['sprice']) ? '' : ' <strong>[' . $r['sprice'] . ']</strong>')
          . ($r['encash'] == 2
            ? ' <strong>GH</strong>'
            : ($r['encash'] == 3
              ? '<img src="' . base_url(
                paths()->getAssetsDir('images/buttons/paypal_small.png')) . '" alt="paypal" style="margin-bottom:-3px;"/>' : '')) . (empty($r['memo'])
            ? ''
            : ' <br/>[' . $r['memo'] . ']');
      }
    }

    //билеты
    $temp = Query::sqlQuery(
      'select c.mode - 1 as mode, c.client_id, c.name, c.surname, a.title as area, a.area_id, 
        tp.start as period_start, tp.finish as period_finish,
        substring(t.time_start,1,5) as time_start, substring(t.time_finish,1,5) as time_finish, t.weekdays, at.title as type, asp.title as sport,
        if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period, 
        t.space, if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin,
         t.ticket_id, tp.period_id, a.period as time_period,
             cs.title as club_state    
      from ' . Query::tableName('tickets_periods') . ' tp
      left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id
      left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
      left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
      left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
      left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id
       left join ' . Query::tableName('config_club_state') . ' cs on cs.id = c.club_state 
      where (("' . $mysql_start_date . '" >= substring(tp.start,1,10) AND "' . $mysql_start_date . '" <= substring(tp.finish,1,10)) 
      OR ("' . $mysql_end_date . '" >= substring(tp.start,1,10) AND "' . $mysql_end_date . '" <= substring(tp.finish,1,10)) 
      OR ("' . $mysql_start_date . '" <= substring(tp.start,1,10) AND "' . $mysql_end_date . '" >= substring(tp.finish,1,10)))
      ' . ((isset($type_id) && $type_id != 0) ? ' and at.type_id = ' . $type_id : '') .
      (isset($sport_id) && $sport_id != 0 ? ' and asp.sport_id = ' . $sport_id : '')
    );

    foreach ($temp as $r) {
      $tickets_list[$r['ticket_id']] = $r['ticket_id'];
    }

    foreach ($tickets_list as $tick => $val) {
      $pdata_temp = [];
      $engine->tickets->getTicketFullDataWithPeriodsAndPriceById($tick, $tdata_temp, $pdata_temp);
      $pdata[$tick] = current($pdata_temp);
    }

    foreach ($temp as $r) {
      if (!isset ($clients[$r['client_id']])) {
        $clients[$r['client_id']] = array(($r['surname'] ? $r['surname'] . ' ' : '') . $r['name'], $r['mode'],);
      }

      $day_start  = substr($r['period_start'], 0, 10) > $mysql_start_date
        ? (int)substr($r['period_start'], 8)
        : date(
          'd',
          $start_unixtime
        );
      $date_start = substr($r['period_start'], 0, 10) > $mysql_start_date
        ? substr($r['period_start'], 0, 7)
        : date(
          'Y-m',
          $start_unixtime
        );

      //раскидываем билет на одиночные заказы по всему месяцу
      $ticket_weekdays = explode(',', $r['weekdays']);
      $weekday         = CalendarHelper::getWeekdayByUnixtime(strtotime(substr($date_start, 0, 7) . '-' . $day_start));

      $current_day_unix = strtotime(substr($date_start, 0, 7) . '-' . $day_start);
      $week             = $r['week_begin'] !== null ? $r['week_begin'] : date('W', strtotime($r['period_start']));
      $first_day        = CalendarHelper::getFirstDayTicket($r['min_start_period'], $ticket_weekdays, $week);
      while (strtotime($mysql_end_date) >= $current_day_unix) {
        $current_week = date('W', $current_day_unix);
        if (in_array($weekday, $ticket_weekdays)
          && CalendarHelper::checkDayForTicket($current_day_unix, $first_day[$weekday], $r['space'])
          && $current_day_unix <= strtotime($r['period_finish'])) {
          $des_times = $engine->tickets->getDisabledTime($mysql_start_date,
            $mysql_end_date, date('Y-m-d', $current_day_unix), $r['ticket_id']);
          foreach ($engine->tickets->buildTimeTickets($r, $des_times) as $ticket_row) {
            $price_tic = 0;
            $p_id      = $engine->areas->getPeriodByDate($current_date);
            foreach ($pdata[$r['ticket_id']]['price'][$weekday] as $time_pr => $price_val) {
              $price_tic += $price_val[$p_id];
            }
            $periods[$ticket_row['type']][$ticket_row['sport']][date(
              'Y-m-d',
              $current_day_unix
            )][$ticket_row['area_id'] . '#' . $ticket_row['time_start'] . ':00']
              = array(
              $ticket_row['area'],
              TimeHelper::convertTime24($ticket_row['time_finish'], false),
              $ticket_row['client_id'],
              7            => 'abo',
              'club_state' => ConfigClubStateModel::getItemById($all_clients[$ticket_row['client_id']]->club_state, 'title'),
              'price'      => $price_tic
            );
          }
        }
        //считаем следующий день недели
        $weekday          += (int)($weekday == 6 ? -$weekday : 1);
        $current_day_unix = mktime(
          0,
          0,
          0,
          date('m', $current_day_unix),
          (date('d', $current_day_unix) + 1),
          date('Y', $current_day_unix)
        );
      }
    }
//    Debug()::dvDD($periods);
    $out .= "<div id=\"main\">";
    $out .= "<div id=\"top\">\n";
    $out .= "<h1>" . config('app')->getProjectTitle() . "<br>" . (isset($type_title) ? $type_title . ($sport_id ? ' - ' . $sport_title->title
          : '') . '<br>'
        : '') . "<b>" . $start_day . "/" . $start_month . "/" . $start_year . " - " . $end_day . "/" . $end_month . "/" . $end_year . "</b></h1>\n";
    $out .= "</div>\n";

    $price_overall = 0;

    $out .= drawDateDivs($periods, $clients);

    $out .= "</div>\n";
  } else {
    $out = lang('Incorrect date', 'message_error');
  }
} else {
  $out = lang('Date not presented', 'message_error');
}

$_page['content'][0] = $out;
$_page['key']        = 'report_reservations_for_period';


function drawDateDivs($periods, $clients)
{
  $out = '';
  if (is_array($periods)) {
    foreach ($periods as $type_title => $sports) {
      foreach ($sports as $sport => $data) {
        $out .= "<div class=\"typeTitle\">" . (count($periods) > 1 ? $type_title . ' - ' : '') . $sport . "</div>\n";

        if (is_array($data)) {
          ksort($data);
          foreach ($data as $date => $reservation) {
            ksort($reservation);
            $out .= "<div class=\"client\">\n<div class=\"title\">";
            $out .= date("d.m.Y", strtotime($date));
            $out .= "</div>\n";
            $out .= "<div class=\"periods\">\n";
            $out .= "<table cellspacing=\"0\" class=\"periods\">\n";

            if (is_array($reservation)) {
              foreach ($reservation as $start => $tmp) {
                $out .= "<tr><td class=\"a\" style=\"width:20%\">" . $tmp[0] . "</td><td class=\"p\" style=\"width:20%\">" . date(
                    'H:i',
                    strtotime(
                      substr(
                        $start,
                        -8
                      )
                    )
                  ) . " - " . $tmp[1] . "</td><td class=\"a\"style=\"width:50%\">" . $clients[$tmp[2]][0] . ' (' . ($clients[$tmp[2]][1]
                    ? (!is_numeric($tmp[2]) ? lang('Guest', 'reports') : lang('Offline client', 'reports'))
                    : lang('Online client',
                      'reports')) . ')' . "</td>" . "<td style=\"width:10%\">" . $tmp['friends'] . "</td>" . '<td style="width:10%">' . $tmp['club_state'] . '</td>' . "<td class=\"p" . ((isset ($tmp[4]) && $tmp[4] == 0)
                    ? ' encash' : '') . "\" style=\"width:10%\">" . (isset ($tmp[3]) ?
                    number_format(
                      $tmp[3],
                      2,
                      ',',
                      ' '
                    ) . ' ' . CURR_VALUTE . ' <span style="color:blue">L:</span> ' .
                    number_format(
                      $tmp[5],
                      2,
                      ',',
                      ' '
                    ) . ' ' . CURR_VALUTE . ' <span style="color:blue">H:</span> ' .
                    number_format(
                      $tmp[6],
                      2,
                      ',',
                      ' '
                    ) . ' ' . CURR_VALUTE . '' : (number_format(
                      $tmp['price'],
                      2,
                      ',',
                      ' '
                    ) . ' € ' . '<strong>' . lang('Abo') . '</strong>')) . "</td></tr>\n";
              }
            }
            $out .= "</table>\n";
            $out .= "</div>\n</div>\n\n";
          }
        }
      }
    }
  }

  return $out;
}
