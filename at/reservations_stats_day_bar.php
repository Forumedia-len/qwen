<?php
//
//use AC\core\engines\Engines;
//use AC\core\system\db\Query;
//use AC\core\system\helpers\CalendarHelper;
//use AC\core\system\helpers\TimeHelper;
//
//
//$out = '';
//
//if (isset($_GET['year']) && isset($_GET['month']) && isset($_GET['day'])) {
//  $type_id = (int)$_GET['type_id'];
//
//
//  //запрашиваем название типа площадок
//  $temp1 = Query::sqlQuery(
//    'select title, type_id from ' . Query::tableName('areas_types') . '  WHERE active="1" order by sort',
//    [],
//    true,
//    ['style' => PDO::FETCH_NUM]
//  );
//
//  if (!empty($temp1)) {
//    $year  = $_GET['year'];
//    $month = $_GET['month'];
//    $day   = $_GET['day'];
//
//    $interval = ['00:00:00', '23:59:59'];
//    if (isset($_GET['interval'])) {
//      if ($_GET['interval'] != 'all') {
//        $interval    = explode('|', $_GET['interval']);
//        $interval[0] = (isset($interval[0]) ? $interval[0] . ':00' : '00:00:00');
//        $interval[1] = (isset($interval[1]) ? $interval[1] . ':00' : '23:59:59');
//      }
//    }
//
//    $unixtime = strtotime("$year-$month-$day");
//
//    $out .= "<div id=\"main\">";
//    $out .= "<div id=\"top\">\n";
//    $out .= "<h1>" . config('app')->getProjectTitle() . "<br><b>Kassenbericht  - " . date("d.m.Y", $unixtime) . "</b></h1>\n";
//    $out .= "</div>\n";
//
//    Query::sqlQuery("DROP TABLE IF EXISTS tmp", [], false);
//    Query::sqlQuery(
//      'CREATE TEMPORARY TABLE tmp (client_id INT UNSIGNED NOT NULL,
//		name VARCHAR (255) NOT NULL,
//		surname VARCHAR (255) NOT NULL,
//		area VARCHAR (255) NOT NULL, stock VARCHAR (255), sprice VARCHAR (255), encash SET(\'0\',\'1\',\'2\',\'3\',\'4\') NOT NULL, memo VARCHAR (255) NOT NULL, start VARCHAR (5) NOT NULL, finish VARCHAR (5) NOT NULL,
//		price DOUBLE, light_price DOUBLE, heating_price DOUBLE, client_mode ENUM("1","2","3")  NOT NULL)',
//      [],
//      false
//    );
//    $all_sum    = 0;
//    $sum_encash = ['ec' => 0, 'bar' => 0, 'dv' => 0];
//
//    $i                   = 0;
//    $array_sum_price     = [];
//    $title_sum           = [
//      'card'     => 'Summe Kartenzahlung',
//      'cash'     => 'Summe Barzahlung',
//      //'payone'    => 'Summe Payone Zahlungen',
//      'rechnung' => 'Summe der Online Kunden mit Login (Rechnung)',
//      'guthaben' => 'Summe Zahlung mit Online-Guthaben',
//      'offline:' => 'Summe der Offline-Kunden',
////          'bar:' => 'Summe der Offline-Kunden',
//    ];
//    $type_titles         = [];
//    $bar_reservation_sum = [];
//    /**
//     * @var reservation $r
//     */
//    $r = new Engines();
//    foreach ($r->bar->getBarTitles(true) as $barTitle) {
//      $bar_reservation_sum[$barTitle['title']] = ['bar' => 0, 'card' => 0];
//    }
////    $bar_reservation_sum = array(
//    //      'Getränke-Automat' => array('bar' => 0, 'card' => 0),
//    //      'Lichtmarken'      => array('bar' => 0, 'card' => 0),
//    //      'Dusche'           => array('bar' => 0, 'card' => 0),
//    //      'Sonstiges'        => array('bar' => 0, 'card' => 0),
//    //    );
//    $i = 0;
//    while ($type_row = mysql_fetch_row($link_type)) {
//      $output                = '';
//      $type_title            = $type_row[0];
//      $type_id               = $type_row[1];
//      $type_titles[$type_id] = $type_title;
//      $mysql_date            = "$year-$month-$day";
//      if (date('Y-m-d', $unixtime) == $mysql_date) {
//        uses('mysqltime', 'calendar');
//
//        if ($mode == 1 || $mode == 3) {
//          //билеты
//          mysql_queryD(
//            'insert into tmp
//            select
//              c.client_id, c.name, c.surname, a.title as area, null, null, null, null,
//              substring(t.time_start,1,5) as start, substring(t.time_finish,1,5) as finish, null as price, null, null, c.mode
//            from ' . DB_TABLE_PREFIX . 'tickets_periods tp
//            left join ' . DB_TABLE_PREFIX . 'tickets t on tp.ticket_id = t.ticket_id
//            left join ' . DB_TABLE_PREFIX . 'areas a on a.area_id = t.area_id
//            left join ' . DB_TABLE_PREFIX . 'areas_types at on at.type_id = a.type_id
//            left join ' . DB_TABLE_PREFIX . 'clients c on t.client_id = c.client_id
//            where "' . $mysql_date . '" between tp.start and tp.finish
//            AND t.time_start >= "' . $interval[0] . '" AND t.time_finish <= "' . $interval[1] . '"
//            and at.type_id = ' . $type_id . ' and
//            find_in_set(' . getWeekdayByUnixtime($unixtime) . ',t.weekdays)'
//          );
//        }
//
//        //обычные заказы
//        mysql_queryD(
//          'insert into tmp
//            select
//              if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id),
//              if(r.client_id is null,r.client_name,c.name),
//              if(r.client_id is null,r.client_surname,c.surname),
//              a.title, st.code, sp.code, r.encash, r.memo,
//              substring(r.start,12,5), substring(r.finish,12,5), r.price, if(r.light_state="1", r.light_price, 0.00), if(r.heating_state="1", r.heating_price, 0.00),  if(r.client_id is null,3,c.mode)
//            from ' . DB_TABLE_PREFIX . 'reservations r
//            left join ' . DB_TABLE_PREFIX . 'areas a on a.area_id = r.area_id
//            left join ' . DB_TABLE_PREFIX . 'areas_types at on at.type_id = a.type_id
//            left join ' . DB_TABLE_PREFIX . 'clients c on r.client_id = c.client_id
//            left join ' . DB_TABLE_PREFIX . 'reservations_stocks st on r.stock_id = st.stock_id
//            left join ' . DB_TABLE_PREFIX . 'reservations_specprice sp on r.sprice_id = sp.sprice_id
//            where r.start >= "' . $mysql_date . ' ' . $interval[0] . '" and  r.start < "' . $mysql_date . ' ' . $interval[1] . '" and at.type_id = "' . $type_id . '"'
//        );
//
//        //извлекаем из временное таблицы периоды всех клиентов
//        $periods = [];
//        $link    = mysql_queryD(
//          'select client_id, area, stock, sprice, encash,  memo, start, finish, price, light_price, heating_price from tmp order by start'
//        );
//        while ($row = mysql_fetch_assoc($link)) {
//          $periods[$row['client_id']][] = $row;
//        }
//        require_once '../config.php';
//
//
//        $price_overall = $price_overall_transaction = 0;
//
//        //периоды клиентов с client_mode = 1
//        //online клиенты которые платят по счетам
//
//        $link    = mysql_queryD(
//          'select client_id, name, surname from tmp where client_mode = 1 and encash = "1" group by client_id order by surname, name'
//        );
//        $out_res = getClientsDivs(
//          $mode,
//          $link,
//          $periods,
//          $price_overall,
//          $price_overall_transaction,
//          $sum_price
//        );
//        if (!empty($out_res)) {
//          $output .= "<div class=\"typeTitle\">$type_title - Online Kunden mit Login (Rechnung)</div>\n";
//          $output .= $out_res;
//        }
//        $array_sum_price['rechnung'][$type_id] = $sum_price;
//
//        //online клиенты которые платят наличкой
//
//        $link    = mysql_queryD(
//          'select client_id, name, surname from tmp where client_mode = 1 and encash = "0" group by client_id order by surname, name'
//        );
//        $out_res = getClientsDivs(
//          $mode,
//          $link,
//          $periods,
//          $price_overall,
//          $sum_encash['bar'],
//          $sum_price,
//          'bar'
//        );
//        if (!empty($out_res)) {
//          $output .= "<div class=\"typeTitle\">$type_title - Online Kunden mit Login (Barzahlung)</div>\n";
//          $output .= $out_res;
//        }
//        $array_sum_price['cash'][$type_id] = $sum_price;
//
//        //online Guthaben
//
//        $link    = mysql_queryD(
//          'select client_id, name, surname from tmp where client_mode = 1 and encash = "2"  group by client_id order by surname, name'
//        );
//        $out_res = getClientsDivs(
//          $mode,
//          $link,
//          $periods,
//          $price_overall,
//          $price_overall_transaction,
//          $sum_price,
//          'gh'
//        );
//        if (!empty($out_res)) {
//          $output .= "<div class=\"typeTitle\">$type_title - Online-Guthaben</div>\n";
//          $output .= $out_res;
//        }
//        $array_sum_price['guthaben'][$type_id] = $sum_price;
//
//        //online клиенты которые платят Payonline
//        /*$out                                 .= "<div class=\"typeTitle\">$type_title - PayOne Zahlungen</div>\n";
//        $link                                = mysql_queryD('select * from tmp where client_mode = 1 and encash = "3" group by client_id order by surname, name');
//        $out                                 .= getClientsDivs($mode, $link, $periods, $price_overall,
//        $price_overall_transaction, $sum_price, 'payonline');
//        $array_sum_price['payone'][$type_id] = $sum_price;*/
//
//        //online клиенты которые платят EC
//
//        $link    = mysql_queryD('select * from tmp where client_mode = 1 and encash = "4" group by client_id order by surname, name');
//        $out_res = getClientsDivs(
//          $mode,
//          $link,
//          $periods,
//          $price_overall,
//          $sum_encash['ec'],
//          $sum_price,
//          'ec'
//        );
//        if (!empty($out_res)) {
//          $output .= "<div class=\"typeTitle\">$type_title - EC</div>\n";
//          $output .= $out_res;
//        }
//        $array_sum_price['card'][$type_id] = $sum_price;
//
//        //периоды клиентов с client_mode = 2
//
//        $link    = mysql_queryD('select client_id, name, surname from tmp where client_mode = 2 group by client_id order by surname, name');
//        $out_res = getClientsDivs($mode, $link, $periods, $price_overall, $price_overall_transaction, $sum_price);
//        if (!empty($out_res)) {
//          $output .= "<div class=\"typeTitle\">$type_title - Offline Kunden ohne Login</div>\n";
//          $output .= $out_res;
//        }
//
//        $array_sum_price['offline'][$type_id] = $sum_price;
//
//        if ($mode == 1 || $mode == 2) {
//          //периоды клиентов с client_mode = 3
//
//          $link    = mysql_queryD('select client_id, name, surname from tmp where client_mode = 3 group by client_id order by surname, name');
//          $out_res = getClientsDivs(
//            1,
//            $link,
//            $periods,
//            $price_overall,
//            $price_overall_transaction,
//            $sum_price
//          );
//          if (!empty($out_res)) {
//            $output .= "<div class=\"typeTitle\">$type_title - Bar-Zahler ohne Login</div>\n";
//            $output .= $out_res;
//          }
//          $array_sum_price['bar'][$type_id] = $sum_price;
//        }
//
//        $all_sum += $price_overall;
//        mysql_query('DELETE FROM tmp');
//        if (!empty($output)) {
//          if ($i !== 0) {
//            $out .= '<br><hr>';
//          }
//          $i++;
//          $out .= "<h1><b>" . $type_title . "</b></h1><br />\n";
//          $out .= $output;
//        }
//      } else {
//        $out = 'Incorrect date';
//      }
//    }
//    $out     .= '<br><hr>';
//    $out     .= '<h1><b>Kassenbericht</b></h1><br />';
//    $link_b  = mysql_queryD(
//      'SELECT * FROM ' . DB_TABLE_PREFIX . 'reservations_bar WHERE in_date BETWEEN "' . $mysql_date . ' ' . $interval[0] . '" AND "' . $mysql_date . ' ' . $interval[1] . '"'
//    );
//    $sum_bar = $sum_bar_cart = $sum_bar_bar = 0;
//    if (mysql_num_rows($link_b) > 0) {
//      $out .= "<div class=\"periods\">\n";
//      $out .= "<table cellspacing=\"0\" class=\"periods\">\n";
//      $out .= "<tr><th>Titel</th><th>Betrag, €</th><th>&nbsp;</th><th>MwSt-Sätze</th><th>" . lang('Comment') . "</th></tr>\n";
//
//      while ($row = mysql_fetch_assoc($link_b)) {
//        $bar_reservation_sum[$row['title']][$row['encash']] += $row['sum'];
//        $out                                                .= "<tr><td class=\"a\">" . $row['title'] . "</td><td class=\"p\">" . number_format(
//            $row['sum'],
//            2,
//            ',',
//            ''
//          ) . " " . CURR_VALUTE . " </td><td>" . ($row["encash'] == 'bar'
//            ? 'Barzahlung'
//            : ($row['encash'] == 'card' ? 'Kartenzahlung'
//              : '')) . "</td ><td class=\"p\">" . $row['nds'] . "% </td><td class=\"a\">" . $row['comment'] . " </td></tr>\n";
//
//        if ($row['encash'] == 'card') {
//          $sum_bar_cart += $row['sum'];
//        } elseif ($row['encash'] == 'bar') {
//          $sum_bar_bar += $row['sum'];
//        }
//
//        $sum_bar          += $row['sum'];
//        $sum_encash['dv'] += $row['sum'];
//      }
//      $out .= "</table>\n";
//      $out .= "</div><br /><br />";
//    }
//
//    $out .= "<div class=\"periods\">\n";
//    $out .= "<table cellspacing=\"0\" class=\"periods\">\n";
//    $out .= "<tr><th>Titel</th><th>Kartenzahlung</th><th>Barzahlung</th>";
//    $out .= "<th>Alle</th></tr>\n";
//    foreach ($bar_reservation_sum as $bar_title => $b_sum) {
//      $out .= "<tr>
//                <td><b>Gesamt „" . $bar_title . "“</b></td>
//                <td class='pp'>" . number_format($b_sum['card'], 2, ',', ' ') . " " . CURR_VALUTE . "</td>
//                <td align='center'>" . number_format($b_sum['bar'], 2, ',', ' ') . " " . CURR_VALUTE . "</td>
//                <td class='pp'>" . number_format(($b_sum['card'] + $b_sum['bar']), 2, ',', ' ') . " " . CURR_VALUTE . "</td></tr>";
//    }
//    $out .= "<tr>
//                <td><b>Gesamt </b></td>
//                <td class='pp'>" . number_format($sum_bar_cart, 2, ',', ' ') . " " . CURR_VALUTE . "</td>
//                <td align='center'>" . number_format($sum_bar_bar, 2, ',', ' ') . " " . CURR_VALUTE . "</td>
//                <td class='pp'>" . number_format(($sum_bar_cart + $sum_bar_bar), 2, ',', ' ') . " " . CURR_VALUTE . "</td></tr>";
//    $out .= "</table></div><br /><br />";
//
//    $out .= "<div class=\"periods\">\n";
//    $out .= "<table cellspacing=\"0\" class=\"periods\">\n";
//    $out .= "<tr><th>Titel</th><th>Kassenbericht</th>";
//    foreach ($type_titles as $type_title) {
//      $out .= "<th>$type_title</th>";
//    }
//    $out .= "<th>Alle</th></tr>\n";
//    foreach ($title_sum as $alias => $title) {
//      $sum_row_all = 0;
//      switch ($alias) {
//        case 'card':
//          $other = $sum_bar_cart;
//          break;
//        case 'cash':
//          $other = $sum_bar_bar;
//          break;
//        default:
//          $other = 0;
//          break;
//      }
//      $sum_row_all += $other;
//      $out         .= "<tr>
//                  <td><b>$title</b></td>
//                  <td class='pp'>" . number_format($other, 2, ',', ' ') . " " . CURR_VALUTE . "</td>";
//      $i           = 0;
//      foreach (array_keys($type_titles) as $type) {
//        $sum_row_all += $array_sum_price[$alias][$type];
//        $out         .= "<td class='" . ($i % 2 != 0 ? 'pp' : '') . "'>" . number_format(
//            $array_sum_price[$alias][$type],
//            2,
//            ',',
//            ' '
//          ) . " " . CURR_VALUTE . "</td>";
//        $i++;
//      }
//
//      $out .= "<td class='pp bgp'>" . number_format($sum_row_all, 2, ',', ' ') . " " . CURR_VALUTE . "</td></tr>";
//    }
//    $out .= "</table></div>";
//
////    $out     .= '<div id="bottom">';
//    //    $out     .= '<p>
//    //              <b>Summe Kartenzahlung: ' . number_format($sum_bar_cart, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b><br />
//    //                            <b>Summe Barzahlung: ' . number_format($sum_bar_bar, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b><br>
//    //                            </p>' . "\n";
//    //    $out     .= '<p><b>Gesamtbetrag: ' . number_format($sum_bar, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b></p><p>&nbsp;</p>' . "\n";
//    //    $out     .= "</div>\n";
//    $out .= '<div id="bottom">';
//    /*$out .= '<p>EC-Zahlungen: ' . number_format ($sum_encash['ec'], 2, ',', ' ') . ' ' . CURR_VALUTE . '</p>';
//    $out .= '<p>Barzahlungen: ' . number_format ($sum_encash['bar'], 2, ',', ' ') . ' ' . CURR_VALUTE . '</p>';
//    $out .= '<p>Diverse Verkäufe: ' . number_format ($sum_encash['dv'], 2, ',', ' ') . ' ' . CURR_VALUTE . '</p>';*/
////    $out .= '<p><b>Zahlungen an der Theke: ' . number_format(($sum_encash['ec'] + $sum_encash['bar'] + $sum_encash['dv']),
//    //        2, ',', ' ') . ' ' . CURR_VALUTE . '</b></p>';
//    $all_sum += $sum_bar;
//    $out     .= '<p><b>GESAMTBETRAG: ' . number_format($all_sum, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b></p>';
//    $out     .= "</div>\n";
//    $out     .= '<p>Ausgedruckt am: ' . date('d.m.Y H:i:s') . "<br/>&nbsp;</p>\n";
//    $out     .= "</div>\n";
//  } else {
//    $out = 'Incorrect type';
//  }
//} else {
//  $out = 'Date not presented';
//}
//
//$_page['content'][0] = $out;
//$_page['key']        = 'reservations_report';
//$_page['title']      = 'Statistik';
//
//
//function getClientsDivs(
//  $mode,
//  $link,
//  &$periods,
//  &$price_overall,
//  &$price_overall_transaction,
//  &$sum_price,
//  $encash = 'account'
//) {
//  $out     = '';
//  $all_sum = 0;
//  if (mysql_num_rows($link) > 0) {
//    $out                = "<div id=\"columnLeft\">\n";
//    $clients_divider_on = ceil(mysql_num_rows($link) / 2);
//    $clients_shown      = 0;
//    while (list($client_id, $name, $surname) = mysql_fetch_row($link)) {
//      if (isset($clients_divider_on) && $clients_shown >= $clients_divider_on) {
//        $out .= "</div>\n\n<div id=\"columnRight\">\n\n";
//        unset($clients_divider_on);
//      }
//
//      //сумма (обычные заказы,абонементы)
//      $sum   = [0, 0];
//      $price = 0;
//      $rows  = '';
//      foreach ($periods[$client_id] as $p) {
//        //разбиваем online клиентов на счета и наличку
//        if ($encash == 'payonline' && (isset($p['encash']) && $p['encash'] != 3)) {
//          continue;
//        } else {
//          if ($encash == 'ec' && (isset($p['encash']) && $p['encash'] != 4)) {
//            continue;
//          } else {
//            if ($encash == 'bar' && (isset($p['encash']) && $p['encash'] != 0)) {
//              continue;
//            } else {
//              if ($encash == 'gh' && (isset($p['encash']) && $p['encash'] != 2)) {
//                continue;
//              } else {
//                if ($encash == 'account' && (isset($p['encash']) && $p['encash'] != 1)) {
//                  continue;
//                }
//              }
//            }
//          }
//        }
//        $rows .= "<tr><td class=\"a\">" . $p['area'] . (!empty($p['stock']) ? ' [' . $p['stock'] . ']' : '') . (!empty($p['sprice'])
//            ? ' <strong>[' . $p['sprice'] . ']</strong>' : '') . ($p['encash'] == 2
//            ? ' <strong>GH</strong>'
//            : ((isset($p['encash']) && $p['encash'] == 3) ? '<img src="' . base_url(paths()->getAssetsDir('images/buttons/paypal_small.png')) . '" alt="paypal" style="margin-bottom:-3px;"/>'
//              : '')) . (!empty($p['memo']) ? '<br/>[' . $p['memo'] . ']'
//            : '') . "</td><td class=\"p\">" . $p['start'] . " - " . $p['finish'] . "</td><td class=\"pr" . ((isset($p['encash']) && $p['encash'] == 0)
//            ? ' encash' : '') . "\">" . ($p['price'] !== null ? number_format(
//              $p['price'],
//              2,
//              ',',
//              ''
//            ) . ' <span style="color:blue">L:</span> ' . number_format(
//              $p['light_price'],
//              2,
//              ',',
//              ' '
//            ) . ' <span style="color:blue">H:</span> ' . number_format(
//              $p['heating_price'],
//              2,
//              ',',
//              ' '
//            ) . ' ' . CURR_VALUTE . '' : 'Abo') . "</td></tr>\n";
//
//        [$h0, $m0] = explode(':', $p['start']);
//        [$h1, $m1] = explode(':', $p['finish']);
//
//        if ($h1 > $h0 || $h1 == 0) {
//          $sum[$p['price'] === null] += 60;
//        }
//        $sum[$p['price'] === null] += $m1 - $m0;
//
//        if ($p['price'] !== null) {
//          $price                     += $p['price'];
//          $all_sum                   += $p['price'];
//          $price_overall             += $p['price'];
//          $price_overall_transaction += $p['price'];
//        }
//      }
//
//      $out .= "<div class=\"client\">\n<div class=\"title\">";
//      $out .= "<span class=\"header\">Kunde</span> " . $surname . ' ' . $name;
//
//      if ($mode == 1 || $mode == 2) {
//        $out .= "<br><span class=\"header\">Buchung</span> " . convertMinutes2MySQLTime($sum[0]) . " / " . number_format(
//            $price,
//            2,
//            ',',
//            ' '
//          ) . " " . CURR_VALUTE;
//      }
//
//      if ($mode == 1 || $mode == 3) {
//        $out .= "<br><span class=\"header\">Abo</span> " . convertMinutes2MySQLTime($sum[1]);
//      }
//
//      $out .= "</div>\n";
//      $out .= "<div class=\"periods\">\n";
//      $out .= "<table cellspacing=\"0\" class=\"periods\">\n" . $rows;
//      $out .= "</table>\n";
//      $out .= "</div>\n</div>\n\n";
//
//      $clients_shown++;
//    }
//    $out .= "</div>\n";
//    $out .= '<div style="clear:both; text-align:left">Gesamtbetrag: ' . number_format(
//        $all_sum,
//        2,
//        ',',
//        ' '
//      ) . ' ' . CURR_VALUTE . '</div><br /><br />' . "\n";
//  }
//  $sum_price = $all_sum;
//
//  return $out;
//}
//
//Query::sqlQuery("DROP TABLE IF EXISTS tmp", [], false);
//Query::sqlQuery(
//  'CREATE TEMPORARY TABLE tmp (client_id INT UNSIGNED NOT NULL,
//		name VARCHAR (255) NOT NULL,
//		surname VARCHAR (255) NOT NULL,
//		area VARCHAR (255) NOT NULL, stock VARCHAR (255), sprice VARCHAR (255), encash SET(\'0\',\'1\',\'2\',\'3\',\'4\') NOT NULL, memo VARCHAR (255) NOT NULL, start VARCHAR (5) NOT NULL, finish VARCHAR (5) NOT NULL,
//		price DOUBLE, light_price DOUBLE, heating_price DOUBLE, client_mode ENUM("1","2","3")  NOT NULL)', [],
//  false
//);
//$all_sum    = 0;
//$sum_encash = ['ec' => 0, 'bar' => 0, 'dv' => 0];
//foreach ($temp1 as $type_row) {
//  $type_title = $type_row[0];
//  $type_id    = (int)$type_row[1];
//
//  $mysql_date = "$year-$month-$day";
//  if (date('Y-m-d', $unixtime) == $mysql_date) {
//
//    if ($mode == 1 || $mode == 3) {
//      //билеты
//      Query::sqlQuery(
//        'insert into tmp
//						select
//							c.client_id, c.name, c.surname, a.title as area, null, null, null, null,
//							substring(t.time_start,1,5) as start, substring(t.time_finish,1,5) as finish, null as price, null, null, c.mode
//						from ' . Query::tableName('tickets_periods') . ' tp
//						left join ' . Query::tableName('tickets') . ' t on tp.ticket_id = t.ticket_id
//						left join ' . Query::tableName('areas') . ' a on a.area_id = t.area_id
//						left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
//						left join ' . Query::tableName('clients') . ' c on t.client_id = c.client_id
//						where "' . $mysql_date . '" between tp.start and tp.finish and
//						at.type_id = ' . (int)$type_id . ' and
//						find_in_set(' . CalendarHelper::getWeekdayByUnixtime($unixtime) . ',t.weekdays)', [],
//        false
//      );
//    }
//
//    //обычные заказы
//    Query::sqlQuery(
//      'insert into tmp
//						select
//							if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id),
//							if(r.client_id is null,r.client_name,c.name),
//							if(r.client_id is null,r.client_surname,c.surname),
//							a.title, st.code, sp.code, r.encash, r.memo,
//							substring(r.start,12,5), substring(r.finish,12,5), r.price, if(r.light_state="1", r.light_price, 0.00), if(r.heating_state="1", r.heating_price, 0.00),  if(r.client_id is null,3,c.mode)
//						from ' . Query::tableName('reservations') . ' r
//						left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
//						left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
//						left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
//						left join ' . Query::tableName('reservations_stocks') . ' st on r.stock_id = st.stock_id
//						left join ' . Query::tableName('reservations_specprice') . ' sp on r.sprice_id = sp.sprice_id
//						where r.start between "' . $mysql_date . ' 00:00:00" and "' . $mysql_date . ' 23:59:59" and at.type_id = "' . (int)$type_id . '"', [],
//      false
//    );
//
//
//    //извлекаем из временное таблицы периоды всех клиентов
//    $periods = [];
//    $temp    = Query::sqlQuery(
//      'select client_id, area, stock, sprice, encash,  memo, start, finish, price, light_price, heating_price from tmp order by start'
//    );
//    foreach ($temp as $row) {
//      $periods[$row['client_id']][] = $row;
//    }
//    require_once '../config.php';
//
//    $out .= "<h1><b>" . $type_title . "</h1><br />\n";
//
//    $price_overall = $price_overall_transaction = 0;
//
//    //периоды клиентов с client_mode = 1
//    //online клиенты которые платят по счетам
//    $out  .= "<div class=\"typeTitle\">Online Kunden mit Login (Rechnung)</div>\n";
//    $temp = Query::sqlQuery(
//      'select client_id, name, surname from tmp where client_mode = 1 and encash = "1" group by client_id order by surname, name', [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    $out  .= getClientsDivs($mode, $temp, $periods, $price_overall, $price_overall_transaction);
//
//    //online клиенты которые платят наличкой
//    $out  .= "<div class=\"typeTitle\">Online Kunden mit Login (Barzahlung)</div>\n";
//    $temp = Query::sqlQuery(
//      'select client_id, name, surname from tmp where client_mode = 1 and encash = "0" group by client_id order by surname, name', [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    //вывод
//    $out .= getClientsDivs($mode, $temp, $periods, $price_overall, $sum_encash['bar'], 'bar');
//    //online Guthaben
//    $out  .= "<div class=\"typeTitle\">Online-Guthaben</div>\n";
//    $temp = Query::sqlQuery(
//      'select client_id, name, surname from tmp where client_mode = 1 and encash = "2"  group by client_id order by surname, name', [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    //вывод
//    $out .= getClientsDivs($mode, $temp, $periods, $price_overall, $price_overall_transaction, 'gh');
//
//    //online клиенты которые платят Payonline
//    $out  .= "<div class=\"typeTitle\">PayOne Zahlungen</div>\n";
//    $temp = Query::sqlQuery(
//      'select * from tmp where client_mode = 1 and encash = "3" group by client_id order by surname, name',
//      [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    //вывод
//    $out .= getClientsDivs($mode, $temp, $periods, $price_overall, $price_overall_transaction, 'payonline');
//    //online клиенты которые платят EC
//    $out  .= "<div class=\"typeTitle\">EC</div>\n";
//    $temp = Query::sqlQuery(
//      'select * from tmp where client_mode = 1 and encash = "4" group by client_id order by surname, name',
//      [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    //вывод
//    $out .= getClientsDivs($mode, $temp, $periods, $price_overall, $sum_encash['ec'], 'ec');
//
//    //периоды клиентов с client_mode = 2
//    $out  .= "<div class=\"typeTitle\">Offline Kunden ohne Login</div>\n";
//    $temp = Query::sqlQuery(
//      'select client_id, name, surname from tmp where client_mode = 2 group by client_id order by surname, name',
//      [],
//      true,
//      ['style' => PDO::FETCH_NUM]
//    );
//    $out  .= getClientsDivs($mode, $temp, $periods, $price_overall, $price_overall_transaction);
//
//    if ($mode == 1 || $mode == 2) {
//      //периоды клиентов с client_mode = 3
//      $out  .= "<div class=\"typeTitle\">Bar-Zahler ohne Login</div>\n";
//      $temp = Query::sqlQuery(
//        'select client_id, name, surname from tmp where client_mode = 3 group by client_id order by surname, name',
//        [],
//        true,
//        ['style' => PDO::FETCH_NUM]
//      );
//      $out  .= getClientsDivs(1, $temp, $periods, $price_overall, $price_overall_transaction);
//    }
//
//    $all_sum += $price_overall;
//    Query::sqlQuery('DELETE FROM tmp', [], false);
//  } else {
//    $out = 'Incorrect date';
//  }
//}
//$out     .= '<h1><b>CK-Kassenbericht</b></h1><br />';
//$temp    = Query::sqlQuery('SELECT * FROM ' . Query::tableName('reservations_bar') . ' WHERE in_date="' . $mysql_date . '"');
//$sum_bar = 0;
//if (!empty($temp)) {
//  $out .= "<div class=\"periods\">\n";
//  $out .= "<table cellspacing=\"0\" class=\"periods\">\n";
//  $out .= "<tr><th>Titel</th><th>Betrag, " . CURR_VALUTE . "</th><th>&nbsp;</th><th>MwSt-Sätze</th><th>" . lang('Comment') . "</th></tr>\n";
//
//  foreach ($temp as $row) {
//    $out              .= "<tr><td class=\"a\">" . $row['title'] . "</td><td class=\"p\">" . number_format(
//        $row['sum'],
//        2,
//        ',',
//        ''
//      ) . " " . CURR_VALUTE . " </td><td>" . ($row['encash'] == 'bar'
//        ? 'Barzahlung'
//        : ($row['encash'] == 'card' ? 'Kartenzahlung'
//          : '')) . "</td><td class=\"p\">" . $row['nds'] . "% </td><td class=\"a\">" . $row['comment'] . " </td></tr>\n";
//    $sum_bar          += $row['sum'];
//    $sum_encash['dv'] += $row['sum'];
//  }
//  $out .= "</table>\n";
//  $out .= "</div><br />";
//}
//
//$out     .= '<div id="bottom">';
//$out     .= '<p><b>Gesamtbetrag: ' . number_format($sum_bar, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b></p><p>&nbsp;</p>' . "\n";
//$all_sum += $sum_bar;
//$out     .= "</div>\n";
//$out     .= '<div id="bottom">';
///*$out .= '<p>EC-Zahlungen: ' . number_format ($sum_encash['ec'], 2, ',', ' ') . ' &euro;</p>';
//$out .= '<p>Barzahlungen: ' . number_format ($sum_encash['bar'], 2, ',', ' ') . ' &euro;</p>';
//$out .= '<p>Diverse Verkäufe: ' . number_format ($sum_encash['dv'], 2, ',', ' ') . ' &euro;</p>';*/
//$out .= '<p><b>Zahlungen an der Theke: ' . number_format(
//    ($sum_encash['ec'] + $sum_encash['bar'] + $sum_encash['dv']),
//    2,
//    ',',
//    ' '
//  ) . ' ' . CURR_VALUTE . '</b></p>';
//
//$out .= '<p><b>GESAMTBETRAG: ' . number_format($all_sum, 2, ',', ' ') . ' ' . CURR_VALUTE . '</b></p>';
//$out .= "</div>\n";
//$out .= '<p>Ausgedruckt am: ' . date('d.m.Y H:i:s') . "<br/>" . CURR_VALUTE . "</p>\n";
//$out .= "</div>\n";
//} else {
//  $out = 'Incorrect type';
//}
//} else {
//  $out = 'Date not presented';
//}
//
//$_page['content'][0] = $out;
//$_page['key']        = 'reservations_report';
//$_page['title']      = 'Statistik';
//
//
//function getClientsDivs($mode, $arr_res, &$periods, &$price_overall, &$price_overall_transaction, $encash = 'account')
//{
//  $out = '';
//  if (!empty($arr_res)) {
//    $out                = "<div id=\"columnLeft\">\n";
//    $clients_divider_on = ceil(count($arr_val) / 2);
//    $clients_shown      = 0;
//    $all_sum            = 0;
//    foreach ($arr_res as $temp) {
//      [$client_id, $name, $surname] = $temp;
//
//      if (isset ($clients_divider_on) && $clients_shown >= $clients_divider_on) {
//        $out .= "</div>\n\n<div id=\"columnRight\">\n\n";
//        unset ($clients_divider_on);
//      }
//
//      //сумма (обычные заказы,абонементы)
//      $sum   = [0, 0];
//      $price = 0;
//      $rows  = '';
//      foreach ($periods[$client_id] as $p) {
//        //разбиваем online клиентов на счета и наличку
//        if ($encash == 'payonline' && (isset($p['encash']) && $p['encash'] != 3)) {
//          continue;
//        } elseif ($encash == 'ec' && (isset($p['encash']) && $p['encash'] != 4)) {
//          continue;
//        } elseif ($encash == 'bar' && (isset($p['encash']) && $p['encash'] != 0)) {
//          continue;
//        } elseif ($encash == 'gh' && (isset($p['encash']) && $p['encash'] != 2)) {
//          continue;
//        } elseif ($encash == 'account' && (isset($p['encash']) && $p['encash'] != 1)) {
//          continue;
//        }
//        $rows .= "<tr><td class=\"a\">" . $p['area'] . (!empty($p['stock']) ? ' [' . $p['stock'] . ']' : '') . (!empty($p['sprice'])
//            ? ' <strong>[' . $p['sprice'] . ']</strong>' : '') . ($p['encash'] == 2
//            ? ' <strong>GH</strong>'
//            : ((isset ($p['encash']) && $p['encash'] == 3) ? '<img src="' . base_url(paths()->getAssetsDir('images/buttons/paypal_small.png')) . '" alt="paypal" style="margin-bottom:-3px;"/>'
//              : '')) . (!empty($p['memo']) ? '<br/>[' . $p['memo'] . ']'
//            : '') . "</td><td class=\"p\">" . $p['start'] . " - " . $p['finish'] . "</td><td class=\"pr" . ((isset ($p['encash']) && $p['encash'] == 0)
//            ? ' encash' : '') . "\">" . ($p['price'] !== null ? number_format(
//              $p['price'],
//              2,
//              ',',
//              ''
//            ) . ' <span style="color:blue">L:</span> ' . number_format(
//              $p['light_price'],
//              2,
//              ',',
//              ' '
//            ) . ' <span style="color:blue">H:</span> ' . number_format($p['heating_price'], 2, ',', ' ') . ' ' . CURR_VALUTE
//            : 'Abo') . "</td></tr>\n";
//
//        [$h0, $m0] = explode(':', $p['start']);
//        [$h1, $m1] = explode(':', $p['finish']);
//
//        if ($h1 > $h0 || $h1 == 0) {
//          $sum[$p['price'] === null] += 60;
//        }
//        $sum[$p['price'] === null] += $m1 - $m0;
//
//        if ($p['price'] !== null) {
//          $price                     += $p['price'];
//          $all_sum                   += $p['price'];
//          $price_overall             += $p['price'];
//          $price_overall_transaction += $p['price'];
//        }
//      }
//
//      $out .= "<div class=\"client\">\n<div class=\"title\">";
//      $out .= "<span class=\"header\">Kunde</span> " . $surname . ' ' . $name;
//
//      if ($mode == 1 || $mode == 2) {
//        $out .= "<br><span class=\"header\">Buchung</span> " . TimeHelper::convertMinutes2MySQLTime($sum[0]) . " / " . number_format(
//            $price,
//            2,
//            ',',
//            ' '
//          ) . " " . CURR_VALUTE;
//      }
//
//      if ($mode == 1 || $mode == 3) {
//        $out .= "<br><span class=\"header\">Abo</span> " . TimeHelper::convertMinutes2MySQLTime($sum[1]);
//      }
//
//      $out .= "</div>\n";
//      $out .= "<div class=\"periods\">\n";
//      $out .= "<table cellspacing=\"0\" class=\"periods\">\n" . $rows;
//      $out .= "</table>\n";
//      $out .= "</div>\n</div>\n\n";
//
//      $clients_shown++;
//    }
//    $out .= "</div>\n";
//    $out .= '<div style="clear:both; text-align:left">Gesamtbetrag: ' . number_format(
//        $all_sum,
//        2,
//        ',',
//        ' '
//      ) . ' ' . CURR_VALUTE . '</div><br /><br />' . "\n";
//  }
//
//  return $out;
//}
