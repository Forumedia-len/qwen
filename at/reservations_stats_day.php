<?php
use AC\core\modules\config\models\ConfigClubStateModel;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\db\Query;
use AC\core\system\helpers\CalendarHelper;
use AC\core\system\helpers\TimeHelper;

use AC\core\engines\Engines;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
$mkt = microtime(true);

$out   = '';
$year  = Service::request()->_('year');
$month = Service::request()->_('month');
$day   = Service::request()->_('day');
//$mode
//1 - all
//2 - only normal
//3 - only tickets
$mode   = Service::request()->_('eventMode') + 1;
$engine = Service::engines();

$tdata = [];
$pdata = [];

$stocks = ModCommHelper::get('stocks', 'stocks/getStocks', ['as' => 'dto'], 'stocks', []);
if ($year !== null && $month !== null &&
  $mode !== null && ($mode == 1 || $mode == 2 || $mode == 3)
) {
  $tbs = [];
  if ($type = Service::request()->_('type_sport')) {
    $tbs[] = $type;
  } else {
    $tbs = array_keys($engine->areas->getSportsByType());
  }
  $out .= "<div id=\"main\">";
  $out .= "<div id=\"top\">\n";
  $out .= "<h1>" . config('app')->getProjectTitle() . "</h1>\n";
  $out .= "</div>\n";
  foreach ($tbs as $t_s) {
    $periods = [];
    [$type_id, $sport_id] = explode('_', $t_s);
    $all_clients = $engine->clients->getClientsByAreaType([0, $type_id]);
    
    $temp1 = Query::sqlQuery(
      'select title from ' . Query::tableName('areas_types') . ' where type_id = ?',
      [(int)$type_id],
      true,
      ['style' => PDO::FETCH_NUM]
    );
    if (!empty($temp1)) {
      [$type_title] = $temp1[0][0];
      $sport_data = false;
      if ($sport_id) {
        $sport_data = $engine->sports->getSportsTitlesByType($type_id, $sport_id, false);
      }
      $mysql_date = "$year-$month-$day";
      $unixtime   = strtotime("$year-$month-$day");
      
      if (checkdate($month, $day, $year)) {
        $clients = [[], [], []];
        
        //кол-во дней в месяце
        //$dm = getDaysInMonth($year, $month);
        
        if ($mode == 1 || $mode == 2) {
          //обычные резервирования
          $temp = $engine->renderReservationData(Query::sqlQuery(
            'select r.reservation_id,
						if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id) as client_id,
						if(r.client_id is null,r.client_name,c.name) as client_name,
						if(r.client_id is null,r.client_surname,c.surname)  as client_surname,
            if(r.main_client_id is null,"",cm.name) as main_name,
            if(r.main_client_id is null,"",cm.surname) as main_surname,
						a.title as area, a.area_id, r.stock_id, sp.code as sprice, r.memo,
						r.start, substring(r.finish,12,5) as finish,
						r.price,
						r.encash,
						if(c.mode is null,2,c.mode - 1) as mode,
						if(r.light_state = \'1\', r.light_price, 0.00) as light_price,
						if(r.heating_state = \'1\', r.heating_price, 0.00) as heating_price,
						if(r.net_state = \'1\', r.net_price, 0.00) as net_price, 
            r.type_reservation,
            r.street_friends, 
            r.main_client_id, 
            r.main_reservation_id,
            cs.title as club_state
            , rd.name, rd.value, rd.entry_id, rd.type_block
					from ' . Query::tableName('reservations') . ' r
					left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
					left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id' .
            ($sport_id ? ' left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id' : '') .
          ' left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
          left join ' . Query::tableName('clients') . ' cm on r.main_client_id = cm.client_id
          left join ' . Query::tableName('reservations_specprice') . ' sp on r.sprice_id = sp.sprice_id
          LEFT JOIN ' . Query::tableName('reservation_data') . ' rd ON rd.reservation_id = r.reservation_id
					left join ' . Query::tableName('config_club_state') . ' cs on cs.id = c.club_state
					where "' . $mysql_date . '" = substring(r.start,1,10) 
            and r.status="1"
            and at.type_id = ' . $type_id .
            ($sport_id ? ' and asp.sport_id = ' . $sport_id : '') . ' ORDER by r.reservation_id'
          ));
          $last_client_id     = '';
          $last_area_id_start = '';
          foreach ($temp as $r) {
            $stocksCode = [];
            $stocksIds  = isset($r['stocks']) ? array_keys($r['stocks']) : [$r['stock_id']];
            foreach ($stocksIds as $stockId) {
              if (isset($stocks[$stockId])) {
                $stocksCode[] = $stocks[$stockId]->code;
              }
            }
            if (!isset ($clients[$r['mode']][$r['client_id']])) {
              $clients[$r['mode']][$r['client_id']] = ($r['client_surname'] ? $r['client_surname'] . ' ' : '') . $r['client_name'];
            }
            
            $list_friends = '';
            if (!empty($r['street_friends'])) {
              $friends = unserialize($r['street_friends'], ['allowed_classes' => false]);
              
              foreach ($friends as $friend) {
                if($friend) {
                  $list_friends .= ', <span style="white-space:nowrap">' . $friend['name'] . ' (' . ConfigClubStateModel::getItemById(
                      $friend['club_state'],
                      'short'
                    ) . ')</span>';
                }
              }
              $list_friends = trim($list_friends, ', ');
            }
            
            if (empty($r['main_client_id'])) {
              $last_client_id                                              = $r['client_id'];
              $last_area_id_start                                          = $r['area_id'] . '#' . $r['start'];
              $periods[$r['client_id']][$r['area_id'] . '#' . $r['start']] = [
                $r['area']
                . (empty($stocksCode) ? '' : ' <strong>[</strong>' . implode(' | ', $stocksCode) . '<strong>]</strong>')
                . (empty($r['sprice']) ? '' : ' <strong>[' . $r['sprice'] . ']</strong>')
                . ($r['encash'] == 2
                  ? ' <strong>GH</strong>'
                  : ($r['encash'] == 3
                    ? '<img src="' . base_url(
                      paths()->getAssetsDir('images/buttons/paypal_small.png')
                    ) . '" alt="paypal" style="margin-bottom:-3px;"/>' : '')) . (empty($r['memo']) ? ''
                  : '<br/>[' . $r['memo'] . ']'),
                TimeHelper::convertTime24($r['finish'], false),
                $r['price'],
                ($r['encash'] == 1 && is_numeric($r['client_id']) ? 1 : $r['encash']),
                $r['light_price'],
                $r['heating_price'],
                $r['net_price'],
                'type'       => 'r',
                'club_state' => ConfigClubStateModel::getItemById($all_clients[$r['client_id']]->club_state, 'title') ?? 'PP ' . lang('Guest'),
                'friends'    => $list_friends
              ];
            } else {
              $periods[$last_client_id][$last_area_id_start][1] = TimeHelper::convertTime24($r['finish'], false);
              $periods[$last_client_id][$last_area_id_start][0] = $r['area']
                . (empty($stocksCode) ? '' : ' <strong>[</strong>' . implode(' | ', $stocksCode) . '<strong>]</strong>')
                . (empty($r['sprice']) ? '' : ' <strong>[' . $r['sprice'] . ']</strong>')
                . ($r['encash'] == 2
                  ? ' <strong>GH</strong>'
                  : ($r['encash'] == 3
                    ? '<img src="' . base_url(
                      paths()->getAssetsDir('images/buttons/paypal_small.png')
                    ) . '" alt="paypal" style="margin-bottom:-3px;"/>' : '')) . (empty($r['memo']) ? ''
                  : '<br/>[' . $r['memo'] . ']');
            }
          }
        }
        //билеты
        if ($mode == 1 || $mode == 3) {
          $temp = Query::sqlQuery(
            'select c.mode - 1 as mode, c.client_id, c.name, c.surname, a.title as area, a.area_id,  
						tp.start as period_start, tp.finish as period_finish,
						substring(t.time_start,1,5) as time_start, substring(t.time_finish,1,5) as time_finish, t.weekdays,
            if(t.period_start is null, (select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), t.period_start) as min_start_period, 
            if(t.week_begin is null,week((select min(start) from ' . Query::tableName('tickets_periods') . ' where ticket_id=t.ticket_id), 3),t.week_begin) as week_begin,
             t.space, t.ticket_id, tp.period_id, a.period as time_period, cs.title as club_state 
					from ' . Query::tableName('tickets_periods') . ' tp
					left join ' . Query::tableName('tickets') . ' t on t.ticket_id = tp.ticket_id
					left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
					left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id' .
            ($sport_id ? ' left join ' . Query::tableName('areas_sports') . ' asp on asp.sport_id = a.sport_id' : '') .
            ' left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id
             left join ' . Query::tableName('config_club_state') . ' cs on cs.id = c.club_state 
					where "' . $mysql_date . '" between substring(tp.start,1,10) and substring(tp.finish,1,10)
					  and at.type_id = ' . $type_id .
            ($sport_id ? ' and asp.sport_id = ' . $sport_id : '')
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
            if (empty ($clients[$r['client_id']])) {
              $clients[$r['mode']][$r['client_id']] = $r['surname'] . ' ' . $r['name'];
            }
            
            //начало и конец действия абонемента в этом месяце
            $day_start  = substr($r['period_start'], 0, 10) == $mysql_date ? (int)substr($r['period_start'], 10) : $day;
            $day_finish = substr($r['period_finish'], 0, 10) == $mysql_date ? (int)substr($r['period_finish'], 10) : $day;
            
            //раскидываем билет на одиночные заказы по всему месяцу
            $ticket_weekdays = explode(',', $r['weekdays']);
            $weekday         = CalendarHelper::getWeekdayByUnixtime(strtotime($mysql_date));
            $week            = $r['week_begin'] !== null ? $r['week_begin'] : date('W', strtotime($r['period_start']));
            $first_day       = CalendarHelper::getFirstDayTicket($r['min_start_period'], $ticket_weekdays, $week);
            for ($i = $day_start; $i <= $day_finish; $i++) {
              $current_day_unix = strtotime($mysql_date);
              if (in_array($weekday, $ticket_weekdays) && CalendarHelper::checkDayForTicket($current_day_unix, $first_day[$weekday], $r['space'])) {
                $current_date = date('Y-m-d', $current_day_unix);
                $des_times    = $engine->tickets->getDisabledTime($mysql_date, $mysql_date, $current_date, $r['ticket_id']);
                foreach ($engine->tickets->buildTimeTickets($r, $des_times) as $ticket_row) {
                  $price_tic = 0;
                  $p_id      = $engine->areas->getPeriodByDate($current_date);
                  foreach ($pdata[$r['ticket_id']]['price'][$weekday] as $time_pr => $price_val) {
                    $price_tic += $price_val[$p_id];
                  }
                  $periods[$ticket_row['client_id']][$ticket_row['area_id'] . '#' . $current_date . ' ' . $ticket_row['time_start']] = [
                    $ticket_row['area'],
                    TimeHelper::convertTime24($ticket_row['time_finish'], false),
                    $price_tic,
                    '1',
                    'type'       => 'a',
                    'club_state' => ConfigClubStateModel::getItemById($all_clients[$r['client_id']]->club_state, 'title')
                  ];
                }
              }
              //считаем следующий день недели
              $weekday += $weekday == 6 ? -$weekday : 1;
            }
          }
        }
        //сортируем массивы по за значению (фамилия киента, имя клиента)
        asort($clients[0]);
        asort($clients[1]);
        asort($clients[2]);
        
        $out .= "<h2><b>" . $engine->areas->getTitleByTypeAndSport(
            $type_id,
            $sport_id
          ) . ' - ' . $day . "." . $month . "." . $year . " (" .
          ($mode == 1 ? lang('General', 'reports') : ($mode == 2 ? lang('Individual lessons only', 'reports') : lang('Abos only', 'reports'))) .
          ")</b></h2>\n";
        
        $price_overall = 0;
        
        //клиенты
        if (config('payment')->useInvoicePayment()) {
          $out .= "<div class=\"typeTitle\">" . lang('Online customers with login (invoice)', 'reports') . "</div>\n";
          if (!empty ($clients[0])) {
            $out .= drawClientDivs($mode, $clients[0], $price_overall, $periods);
          }
        }
        
        $out .= "<div class=\"typeTitle\">" . lang('Online customers with login (cash payment)', 'reports') . "</div>\n";
        if (!empty ($clients[0])) {
          $out .= drawClientDivs($mode, $clients[0], $price_overall, $periods, 'bar');
        }
        if (OnlineGatewayService::paypalConfig()->showInStats()
          && config('payment')->usePaypalPayment()) {
          //online клиенты которые платят PayPal
          $out .= "<div class=\"typeTitle\">" . lang('PayPal', 'reports') . "</div>\n";
          if (!empty ($clients[0])) {
            $out .= drawClientDivs($mode, $clients[0], $price_overall, $periods, 'paypal');
          }
        }
        
        //online клиенты которые платят карточкой наместе
        $out .= "<div class=\"typeTitle\">" . lang('EC', 'reports') . "</div>\n";
        if (!empty ($clients[0])) {
          $out .= drawClientDivs($mode, $clients[0], $price_overall, $periods, 'ec');
        }

//      $out .= "<div class=\"typeTitle\">Offline Kunden ohne Login</div>\n";
//      if (!empty ($clients[1])) {
//        $out .= drawClientDivs($mode, $clients[1], $price_overall);
//      }
        
        if ($mode == 1 || $mode == 2) {
          $out .= "<div class=\"typeTitle\">" . lang('Cash payer without login', 'reports') . "</div>\n";
          if (!empty ($clients[2])) {
            $out .= drawClientDivs($mode, $clients[2], $price_overall, $periods, 'guest');
          }
        }
        
        $out .= '<div id="bottom">';
        if ($mode == 1 || $mode == 2) {
          $out .= '<b><p>' . lang('Total amount', 'reports') . ': ' . number_format($price_overall, 2, ',', ' ') . ' ' . CURR_VALUTE . '</p></b>';
        }
        $out .= lang('Printed on', 'reports') . ': ' . date('d.m.Y H:i:s');
        $out .= "</div>\n";
        
        $out .= "<hr>\n";
      } else {
        $out = lang('Incorrect date', 'message_error');
      }
    } else {
      $out = lang('Incorrect type', 'message_error');
    }
  }
  $out .= "</div>\n";
} else {
  $out = lang('Date not presented', 'message_error');
}

$_page['content'][0] = $out;
$_page['key']        = 'report_reservations_for_day';

function drawClientDivs($mode, $data, &$price_overall, &$periods, $encash = 'account')
{
  $out = '';
  if (!empty ($data)) {
    //клиенты
    
    $out = "<div id=\"columnLeft\">\n";
    
    $cnt = 0;
    foreach ($data as $client_id => $name_surname) {
      if (!empty($periods[$client_id]) && is_array($periods[$client_id])) {
        $cnt += 1;
      }
    }
    $clients_divider_on = ceil($cnt / 2);
    $clients_shown      = 0;
    $all_sum            = 0;
    $all_sum_type       = [0, 0];
    foreach ($data as $client_id => $name_surname) {
      if (!empty($periods[$client_id]) && is_array($periods[$client_id])) {
        if (isset ($clients_divider_on) && $clients_shown >= $clients_divider_on) {
          $out .= "</div>\n\n<div id=\"columnRight\">\n\n";
          unset ($clients_divider_on);
        }
        ksort($periods[$client_id]);
        //сумма (обычные заказы,абонементы)
        $sum   = [0, 0];
        $price = [0, 0];
        $rows  = '';
        foreach ($periods[$client_id] as $start => $tmp) {
          //разбиваем online клиентов на счета и наличку
          if ($encash == 'paypal' && (isset($tmp[3]) && $tmp[3] != 3)) {
            continue;
          } else {
            if ($encash == 'ec' && (isset($tmp[3]) && $tmp[3] != 4)) {
              continue;
            } else {
              if ($encash == 'bar' && (isset($tmp[3]) && ($tmp[3] != 2 && $tmp[3] != 0))) {
                continue;
              } else {
                if ($encash == 'account' && (isset($tmp[3]) && $tmp[3] != 1)) {
                  continue;
                } elseif ($encash == 'guest' && (isset($tmp[3]) && !($tmp[3] == 0 || $tmp[3] == 1 || $tmp[3] == 3))) {
                  continue;
                }
              }
            }
          }
          
          $t     = explode('#', $start);
          $start = $t[1];
          $rows  .= "<tr><td class=\"a\">" . $tmp[0] . "</td><td class=\"p\">" . date(
              'd.m.Y H:i',
              strtotime($start)
            ) . " - " . $tmp[1] . "</td><td class=\"pr" . ((isset ($tmp[3]) && $tmp[3] == 0) ? ' encash' : '') . "\">" . ($tmp[2] !== false
              ? number_format(
                $tmp[2],
                2,
                ',',
                ' '
              ) . ' ' . CURR_VALUTE . ' <span style="color:blue">L:</span> ' . number_format(
                $tmp[4],
                2,
                ',',
                ' '
              ) . ' ' . CURR_VALUTE . ' <span style="color:blue">H:</span> ' . number_format(
                $tmp[5],
                2,
                ',',
                ' '
              ) . ' ' . CURR_VALUTE : lang('Abo')) . (($tmp['type'] == 'a') ? (' ' . lang('Abo')) : '') . "</td>
              
              <td>
              " . $tmp['friends'] . "
              </td>
                          </tr>\n";
          
          [$h0, $m0] = explode(':', substr($start, 11, 5));
          [$h1, $m1] = explode(':', $tmp[1]);
          if ($h1 > $h0 || $h1 == 0) {
            $sum[$tmp['type']] += ($h1 - $h0) * 60;
          }
          $sum[$tmp['type']] += $m1 - $m0;
          
          if ($tmp[2] !== false) {
            $sate_price                 = $tmp[4] + $tmp[5] + $tmp[6];
            $price[$tmp['type']]        += $tmp[2] + $sate_price;
            $all_sum_type[$tmp['type']] += $tmp[2] + $sate_price;
            $all_sum                    += $tmp[2] + $sate_price;
            $price_overall              += $tmp[2] + $sate_price;
          }
        }
        
        if (!empty($rows)) {
          $out .= "<div class=\"client\">\n<div class=\"title\">";
          $out .= "<span class=\"header\">" . lang('Client') . ": </span> " . $name_surname . ' (' . $tmp['club_state'] . ')';
          
          if ($mode == 1 || $mode == 2) {
            $out .= "<br><span class=\"header\">" . lang('Booking', 'reports') . ": </span> " . TimeHelper::convertMinutes2MySQLTime(
                $sum['r']
              ) . " / " . number_format(
                $price['r'],
                2,
                ',',
                ' '
              ) . " " . CURR_VALUTE . "";
          }
          
          if ($mode == 1 || $mode == 3) {
            $out .= "<br><span class=\"header\">" . lang('Abo',
                'reports') . ": </span> " . TimeHelper::convertMinutes2MySQLTime($sum['a']) . " / " . number_format(
                $price['a'],
                2,
                ',',
                ' '
              ) . " " . CURR_VALUTE . "";
          }
          $out .= "</div>\n";
          $out .= "<div class=\"periods\">\n";
          $out .= "<table cellspacing=\"0\" class=\"periods\">\n" . $rows;
          $out .= "</table>\n";
          $out .= "</div>\n</div>\n\n";
        }
        $clients_shown++;
      }
    }
    $out .= "</div>\n";
    if ($encash == 'account') {
      $out .= '<div style="clear:both; text-align:left;margin-bottom:5px;">' . lang('Buchung', 'reports') . ': ' . number_format(
          $all_sum_type['r'],
          2,
          ',',
          ' '
        ) . ' ' . CURR_VALUTE . '</div>' . "\n";
      $out .= '<div style="clear:both; text-align:left;margin-bottom:5px;">' . lang('Abo', 'reports') . ': ' . number_format(
          $all_sum_type['a'],
          2,
          ',',
          ' '
        ) . ' ' . CURR_VALUTE . '</div>' . "\n";
    }
    $out .= '<div style="clear:both; text-align:left">' . lang('Total amount', 'reports') . ': ' . number_format(
        $all_sum,
        2,
        ',',
        ' '
      ) . ' ' . CURR_VALUTE . '</div><br /><br />' . "\n";
  }
  
  return $out;
}
