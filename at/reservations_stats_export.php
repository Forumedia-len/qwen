<?php
//todo: fix переработаь выборку клиентов по типам для всех отчетов,
// а также проблема с абонементами при запросе запрашиваем каждый абоненмент через функцию(в базе) для каждого Id куча заперосов к базе
// жрет много времени

use AC\core\engines\Engines;
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

$engine = Service::engines();
$stocks = ModCommHelper::get('stocks', 'stocks/getStocks', ['as' => 'dto'], 'stocks', []);
$action = Service::request()->_get('action');

if ($type = Service::request()->_('type_sport')) {
  [$type_id, $sport_id] = explode('_', $type);
}
$_mode = Service::request()->_('eventMode', 0) + 1;
$arrayTypes = [0];
if ($type_id) {
  $arrayTypes[] = $type_id;
}
$all_clients = $engine->clients->getClientsByAreaType(array_unique($arrayTypes));
$areas_prices = [];
$out          = '';
$error        = false;
$fields       = [
  'title_row' => 2,
  'fields'    => [
    'title'           => ['row' => 2, 'col' => 1, 'title' => lang('Customer Login', 'reports'), 'parent' => null],
    'mode'            => ['row' => 2, 'col' => 1, 'title' => lang('Guest_player'), 'parent' => null],
    'encash'          => ['row' => 2, 'col' => 1, 'title' => lang('Payment method'), 'parent' => null],
    'area_type'       => ['row' => 1, 'col' => 1, 'title' => lang('Type'), 'parent' => lang('Place')],
    'area_sport'      => ['row' => 1, 'col' => 1, 'title' => lang('Sport'), 'parent' => lang('Place')],
    'area'            => ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Place')],
    'date'            => ['row' => 2, 'col' => 1, 'title' => lang('Date'), 'parent' => null],
    'time'            => ['row' => 2, 'col' => 1, 'title' => lang('Time period'), 'parent' => null],
    'client'          => ['row' => 2, 'col' => 1, 'title' => lang('Client'), 'parent' => null],
    'abo'             => ['row' => 2, 'col' => 1, 'title' => lang('Abo'), 'parent' => null],
    'club_state'      => ['row' => 2, 'col' => 1, 'title' => lang('Member status'), 'parent' => null],
    'friends'         => ['row' => 2, 'col' => 1, 'title' => lang('Guest'), 'parent' => null],
    'price'           => ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Costs', 'reports')],
    'light_price'     => ['row' => 1, 'col' => 1, 'title' => lang('Light'), 'parent' => lang('Costs', 'reports')],
    'heating_price'   => ['row' => 1, 'col' => 1, 'title' => lang('Heating'), 'parent' => lang('Costs', 'reports')],
    'comment'         => ['row' => 2, 'col' => 1, 'title' => lang('Comment field', 'reports'), 'parent' => null],
    'sp_code'         => ['row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Special price')],
    'sp_rate'         => ['row' => 1, 'col' => 1, 'title' => lang('Price'), 'parent' => lang('Special price')],
    'lt_code'         => ['row' => 1, 'col' => 1, 'title' => lang('Code'), 'parent' => lang('Booking options')],
    'lt_rate'         => ['row' => 1, 'col' => 1, 'title' => lang('Set'), 'parent' => lang('Booking options')],
    'discount_client' => ['row' => 1, 'col' => 1, 'title' => lang('Client'), 'parent' => lang('Discount')],
    'discount_abo'    => ['row' => 1, 'col' => 1, 'title' => lang('Abo'), 'parent' => lang('Discount')],
    'mwst'            => ['row' => 2, 'col' => 1, 'title' => lang('NDS'), 'parent' => null],
  ]
];

if (isset ($_GET['start_year']) && isset ($_GET['start_month']) && isset ($_GET['start_day']) &&
  isset ($_GET['end_year']) && isset ($_GET['end_month']) && isset ($_GET['end_day'])) {
  $start_year  = $_GET['start_year'];
  $start_month = $_GET['start_month'];
  $start_day   = $_GET['start_day'];
  
  $mysql_start_date = "$start_year-$start_month-$start_day";
  $start_unixtime   = strtotime("$start_year-$start_month-$start_day ");
  
  $end_year  = $_GET['end_year'];
  $end_month = $_GET['end_month'];
  $end_day   = $_GET['end_day'];
  
  $mysql_end_date = "$end_year-$end_month-$end_day";
  $end_unixtime   = strtotime("$end_year-$end_month-$end_day ");
  
  if (checkdate($start_month, $start_day, $start_year) && checkdate(
      $end_month,
      $end_day,
      $end_year
    ) && $mysql_end_date >= $mysql_start_date) {
    if ($_mode == 1 || $_mode == 2) {
      //обычные резервирования
      $temp = $engine->renderReservationData(Query::sqlQuery(
        'select
            r.reservation_id,
            if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id) as client_id,
            if(r.client_id is null,r.client_name,c.name) as client_name,
            if(r.client_id is null,r.client_surname,c.surname)  as client_surname,
            a.type_id,
            a.sport_id,
            at.title as type,
            asp.title as sport,
            a.area_id,
            a.title as area,
            r.stock_id,
            sp.code as sp_code,
            sp.rate as sp_rate, 
            r.memo,
            r.start, substring(r.finish,12,5) as finish,
            r.price,
            if(r.light_state="1", r.light_price, 0.00) as light_price,
            if(r.heating_state="1", r.heating_price, 0.00) as heating_price,
            if(r.net_state="1", r.net_price, 0.00) as net_price,
            r.encash, 
            if(c.mode is null, 3, c.mode) as mode,
 r.street_friends,
            c.discount as client_discount,    
            cd.dimension as discount_dimension,    
            cd.retail as discount_retail,    
            cs.title as club_state,r.main_client_id,
            if(nds.rate is null, (SELECT rate FROM ' . Query::tableName('config_nds') . ' WHERE set_default="1"), nds.rate) as mwst
           , rd.name, rd.value, rd.entry_id, rd.type_block
					from ' . Query::tableName('reservations') . ' r
					left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
					left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
					left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id
					left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
          left join ' . Query::tableName('config_nds') . ' nds on c.nds = nds.nds_id
					left join ' . Query::tableName('reservations_specprice') . ' sp on r.sprice_id = sp.sprice_id
          LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON rd.reservation_id = r.reservation_id
					left join ' . Query::tableName('config_discount') . ' cd on c.discount = cd.discount_id
          left join ' . Query::tableName('config_club_state') . ' cs on cs.id = c.club_state 
					where ("' . $mysql_start_date . '" <= substring(r.start,1,10) and "' . $mysql_end_date . '" >= substring(r.start,1,10))'
        . ((isset($type_id) && $type_id != 0) ? ' and a.type_id = ' . $type_id : '')
        . ((isset($sport_id) && $sport_id != 0) ? ' and a.sport_id = ' . $sport_id : '')
        . ' ORDER BY a.area_id, r.start,r.reservation_id'
      ));
      $data = [];
      foreach ($temp as $r) {
        $stocksCode = [];
        $stocksIds  = isset($r['stocks']) ? array_keys($r['stocks']) : [$r['stock_id']];
        foreach ($stocksIds as $stockId) {
          if (isset($stocks[$stockId])) {
            $stocksCode['code'][]   = $stocks[$stockId]->code;
            $stocksCode['amount'][] = $stocks[$stockId]->amount();
          }
        }
        if (!isset ($clients[$r['client_id']])) {
          $clients[$r['client_id']] = [$r['client_surname'] . ' ' . $r['client_name'], $r['mode']];
        }
        $date = date(
          'd.m.Y',
          strtotime($r['start'])
        );
        
        $time_finish = date(
          'H:i',
          strtotime(
            $r['finish']
          )
        );
        if (empty($r['main_client_id'])) {
          $time_start   = date(
            'H:i',
            strtotime(
              $r['start']
            )
          );
          $list_friends = '';
          if (!empty($r['street_friends'])) {
            $friends = unserialize($r['street_friends'], ['allowed_classes' => false]);
            
            foreach ($friends as $friend) {
              if($friend) {
                $list_friends .= ($action === 'CSV' ? '' : ', <span style="white-space:nowrap">') . $friend['name'] . ' (' . ConfigClubStateModel::getItemById($friend['club_state'],
                    'short') . ')' . ($action === 'CSV' ? '' : '</span>');
              }
            }
            $list_friends = trim($list_friends, ', ');
          }
          $ind1                                                                                               = $r['mode'] . '_' . $r['encash'];
          $ind2                                                                                               = strtotime($date);
          $ind3                                                                                               = $r['type_id'];
          $ind4                                                                                               = $r['area_id'];
          $ind5                                                                                               = $time_start;
          $data[$r['mode'] . '_' . $r['encash']][strtotime($date)][$r['type_id']][$r['area_id']][$time_start] = [
            'club_state'      => ConfigClubStateModel::getItemById($all_clients[$r['client_id']]->club_state, 'title'),
            'area'            => $r['area'],
            'area_type'       => $r['type'],
            'area_sport'      => $r['sport'],
            'date'            => $date,
            'time'            => $time_start . ' - ' . $time_finish,
            'client'          => $r['client_surname'] . ' ' . $r['client_name'],
            'abo'             => 'N',
            'price'           => number_format($r['price'], 2, ',', '') . ' ' . CURR_VALUTE,
            'light_price'     => number_format($r['light_price'], 2, ',', '') . ' ' . CURR_VALUTE,
            'heating_price'   => number_format($r['heating_price'], 2, ',', '') . ' ' . CURR_VALUTE,
            'comment'         => $r['memo'],
            'sp_code'         => $r['sp_code'],
            'sp_rate'         => ($r['sp_rate'] ? number_format($r['sp_rate'], 2, ',', '') . ' ' . CURR_VALUTE : ''),
            'lt_code'         => $stocksCode['code'] ? implode(($action === 'CSV' ?  ' | ' : '<br>'), $stocksCode['code']) : '',
            'lt_rate'         => $stocksCode['amount'] ? implode(($action === 'CSV' ?  ' | ' : '<br>'), $stocksCode['amount']) : '',
            'discount_client' => $r['client_discount'] ? number_format($r['discount_retail'], 2, ',', '')
              . ($r['discount_dimension'] == 1 ? ' %' : ' ' . CURR_VALUTE) : '',
            'discount_abo'    => '',
            'friends'         => $list_friends,
            'mwst'            => $r['mwst'] . " %",
          ];
        } else {
          $data[$ind1][$ind2][$ind3][$ind4][$ind5]['time'] = $time_start . ' - ' . $time_finish;
        }
      }
    }
    if ($_mode == 1 || $_mode == 3) {
      //билеты
      $temp = Query::sqlQuery(
        'select c.mode as mode, c.client_id, c.name, c.surname, 
            a.title as area, a.area_id, a.type_id, a.period as time_period, 
            1 as encash,    
            tp.start as period_start, tp.finish as period_finish,
            substring(t.time_start,1,8) as time_start, 
            substring(t.time_finish,1,5) as time_finish, t.weekdays, at.title as type, asp.title as sport, 
            if(a.light_on="1", a.light_price, 0.00) as light_price,    
            if(a.heating_on="1", a.heating_price, 0.00) as heating_price,
            t.space, t.title as comment, 
            if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period,
            if(t.week_begin is null, week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3), t.week_begin) as week_begin,
            ce.extra_id, ce.use_time, ce.rate as extra,  
            c.discount as client_discount, dc.ticket as discount_client, dc.dimension as discount_client_dimension, 
            d.ticket as discount_ticket, d.dimension as discount_ticket_dimension, t.ticket_id, tp.period_id,
            cs.title as club_state,
            if(nds.rate is null, (SELECT rate FROM ' . Query::tableName('config_nds') . ' WHERE set_default="1"), nds.rate) as mwst
					from ' . Query::tableName('tickets_periods') . ' tp 
					left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id 
					left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id 
					left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id 
					left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id 
					left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id 
          left join ' . Query::tableName('config_nds') . ' nds on c.nds = nds.nds_id
					left join ' . Query::tableName('config_discount') . ' d on d.discount_id = t.discount_id
    			left join ' . Query::tableName('config_discount') . ' dc on dc.discount_id = c.discount 
    			left join ' . Query::tableName('config_extra') . ' ce on ce.club_state = c.club_state and ce.area_type_id = a.type_id and ce.area_sport_id = a.sport_id 
          left join ' . Query::tableName('config_club_state') . ' cs on cs.id = c.club_state 
					where (("' . $mysql_start_date . '" >= substring(tp.start,1,10) AND "' . $mysql_start_date . '" <= substring(tp.finish,1,10))
      OR ("' . $mysql_end_date . '" >= substring(tp.start,1,10) AND "' . $mysql_end_date . '" <= substring(tp.finish,1,10))
      OR ("' . $mysql_start_date . '" <= substring(tp.start,1,10) AND "' . $mysql_end_date . '" >= substring(tp.finish,1,10)))'
        . ((isset($type_id) && $type_id != 0) ? ' and a.type_id = ' . $type_id : '')
        . ((isset($sport_id) && $sport_id != 0) ? ' and a.sport_id = ' . $sport_id : '')
        . ' ORDER BY a.area_id, t.time_start'
      );
      foreach ($temp as $r) {
        if (!isset($areas_prices[$r['area_id']])) {
          $engine->areas->getAreaPrice($r['area_id'], $area_prices, $err);
          $areas_prices[$r['area_id']] = $area_prices;
        }
        if (!isset ($clients[$r['client_id']])) {
          $clients[$r['client_id']] = [$r['surname'] . ' ' . $r['name'], $r['mode']];
        }
        
        $day_start   = substr($r['period_start'], 0, 10) > $mysql_start_date
          ? (int)substr($r['period_start'], 8)
          : date(
            'd',
            $start_unixtime
          );
        $date_start  = substr($r['period_start'], 0, 10) > $mysql_start_date
          ? substr($r['period_start'], 0, 7)
          : date(
            'Y-m',
            $start_unixtime
          );
        $time_start  = date('H:i', strtotime($r['time_start']));
        $time_finish = date('H:i', strtotime($r['time_finish']));
        
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
            $date         = date('d.m.Y', $current_day_unix);
            $season       = $engine->areas->getPeriodByDate($date);
            $prices       = [
              'price'         => 0,
              'light_price'   => 0,
              'heating_price' => 0,
            ];
            $current_time = strtotime($time_start);
            while ($current_time < strtotime($time_finish)) {
              $end_period = strtotime(
                date('H:i', $current_time) . ' + ' . $r['time_period'] . ' minute'
              );
              $des_times  = $engine->tickets->getDisabledTime($mysql_start_date,
                $mysql_end_date, date('Y-m-d', $current_day_unix), $r['ticket_id']);
              if (!in_array(date('H:i', $current_time), $des_times)) {
                $price = $areas_prices[$r['area_id']][$weekday][2][$season][date('H:i', $current_time)];
                //Скидка для абонемента
                //Цена с учетом скидки абонемента
                if ($r['discount_ticket_dimension'] == 1) {
                  $price -= $price / 100 * $r['discount_ticket'];
                } else {
                  $price -= $r['discount_ticket'];
                }
                //Цена с учетом наценки для не участников клуба
                
                $price += (int)$r['use_time'] ? $engine->extra->getExtraWeekPrice($r['extra_id'], $weekday, date('H:i:s', $current_time))
                  : $r['extra'];
                //Скидка для клиента
                //Цена с учетом скидки клиента
                if ($r['discount_client_dimension'] == 1) {
                  $price -= $price / 100 * $r['discount_client'];
                } else {
                  $price -= $r['discount_client'];
                }
                //Цена с учетом скидки клиента
                $prices['price'] += $price;
                
                $prices['light_price']   += $r['light_price'];
                $prices['heating_price'] += $r['heating_price'];
                $data[$r['mode'] . '_' . $r['encash']][$current_day_unix][$r['type_id']][$r['area_id']][date(
                  'H:i',
                  $current_time
                )]
                                         = [
                  'area'            => $r['area'],
                  'area_type'       => $r['type'],
                  'area_sport'      => $r['sport'],
                  'date'            => $date,
                  'time'            => date('H:i', $current_time) . ' - ' . TimeHelper::convertTime24(
                      date('H:i', $end_period),
                      false
                    ),
                  'client'          => $r['surname'] . ' ' . $r['name'],
                  'abo'             => 'J',
                  'price'           => number_format($price, 2, ',', '') . ' ' . CURR_VALUTE,
                  'light_price'     => number_format($r['light_price'], 2, ',', '') . ' ' . CURR_VALUTE,
                  'heating_price'   => number_format($r['heating_price'], 2, ',', '') . ' ' . CURR_VALUTE,
                  'comment'         => $r['comment'],
                  'sp_code'         => '',
                  'lt_code'         => '',
                  'sp_rate'         => '',
                  'lt_rate'         => '',
                  'discount_client' => (float)$r['discount_client'] > 0 ? number_format($r['discount_client'], 2, ',', '')
                    . ($r['discount_client_dimension'] == 1 ? ' %' : ' ' . CURR_VALUTE) : '',
                  'discount_abo'    => $r['discount_ticket'] ? number_format($r['discount_ticket'], 2, ',', '')
                    . ($r['discount_ticket_dimension'] == 1 ? ' %' : ' ' . CURR_VALUTE) : '',
                  'club_state'      => ConfigClubStateModel::getItemById($all_clients[$r['client_id']]->club_state, 'title'),
                  'mwst'            => $r['mwst'] . " %",
                ];
              }
              $current_time = $end_period;
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
    }
  } else {
    $error = true;
    $out   = lang('Incorrect date', 'message_error');
  }
} else {
  $error = true;
  $out   = lang('Date not presented', 'message_error');
}
if (!$error) {
  if ($action == 'CSV') {
    $out .= showData($data, $fields, true);
    header("Content-Disposition: attachment; filename=stats_export.csv");
    header("Content-Type: application/x-force-download; name=\"stats_export.csv\"");
    echo $out;
    die;
  } else {
    $out .= "<div id=\"main-export\">";
    $out .= "<div id=\"top\">\n";
    $out .= "<h1>" . config('app')->getProjectTitle() . "<br>" . (isset($type_title) ? $type_title . '<br>'
        : '') . "<b>" . $start_day . "/" . $start_month . "/" . $start_year . " - " . $end_day . "/" . $end_month . "/" . $end_year . "</b></h1>\n";
    $out .= "</div>\n";
    $out .= showData($data, $fields, false);
    $out .= "</div>\n";
  }
}


$_page['content'][0] = $out;
$_page['key']        = 'report_reservations_export';


function showData($data, $fields, $csv = false)
{
  $out = '';
  if (!$csv) {
    $out .= '<div class="periods">';
    $out .= '<table border="0" cellspacing="0" class="periods">';
  }
  for ($row = 1; $row <= $fields['title_row']; $row++) {
    $out .= !$csv ? '<tr>' : '';
//    $out  .= $row == 1 ? (!$csv ? '<th rowspan="2">Zahlungsvariante</th>' : 'Zahlungsvariante;') : (!$csv ? '' : ';');
    $last = [];
    $col  = ['title' => null, 'col' => 0];
    foreach ($fields['fields'] as $key => $field) {
      $title          = $field['row'] != $row ? $field['title'] : ($field['parent'] ? $field['parent'] : '');
      $field['title'] = $title;
      if (!$csv) {
        if (($col['col'] > 1 && $col['title'] != $field['title'])) {
          $colspan = $col['col'] > 1 ? ' colspan="' . $col['col'] . '"' : '';
          $out     .= '<th' . $colspan . '>' . $col['title'] . '</th>';
          $col     = ['title' => null, 'col' => 0];
        }
        if ($field['row'] == $row) {
          if ($field['parent'] && ($col['title'] == null || $col['title'] == $field['parent'])) {
            $col['col']   += $field['col'];
            $col['title'] = $field['parent'];
            continue;
          }
        }
        $rowspan = ($row != $field['row'] && $field['row'] > 1 ? ' rowspan="' . $field['row'] . '"' : '');
        $out     .= $title ? '<th' . $rowspan . '>' . $title . '</th>' : '';
      } else {
        $title = !empty($last) && $title == $last['title'] ? '' : $title;
        $out   .= $title . ';';
      }
      $last = $field;
    }
    if (!$csv && $col['col'] > 1) {
      $out .= '<th colspan="' . $col['col'] . '">' . $col['title'] . '</th>';
    }
    $out .= !$csv ? '</tr>' : "\n";
  }
  if (!empty($data)) {
    $_key = array_keys($data);
    natcasesort($_key);
    foreach ($_key as $mode_encash) {
      [$mode_client, $encash] = explode('_', $mode_encash);
      $key = array_keys($data[$mode_encash]);
      natcasesort($key);
      foreach ($key as $date) {
        $types = $data[$mode_encash][$date];
        foreach ($types as $type_id => $areas) {
          foreach ($areas as $area_id => $times) {
            asort($times);
            foreach ($times as $time_start => $client) {
              $out .= !$csv ? '<tr>' : '';
              $i   = 0;
              foreach (array_keys($fields['fields']) as $field) {
                switch ($field) {
                  case 'encash':
                    $value = Engines::getTitleEncash($encash);
                    break;
                  case 'mode':
                    $value = Engines::getTypeClientByMode($mode_client);
                    break;
                  case 'title':
                    $value = Engines::getTypeTitleForReport($mode_encash);
                    break;
                  default :
                    $value = $client[$field];
                    break;
                }
                $out .= $csv ? $value . ';' : '<td' . ($i % 2 == 0 ? ' class="p"' : '') . '>' . $value . '</td>';
                $i++;
              }
              $out .= !$csv ? '</tr>' : "\n";
            }
          }
        }
      }
    }
  }
  
  if (!$csv) {
    $out .= '</table>';
    $out .= '</div>';
  }
  
  return ($csv ? "\xEF\xBB\xBF" : '') . $out;
}