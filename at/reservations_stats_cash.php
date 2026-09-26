<?php
//error_reporting (E_ALL);

use AC\core\system\db\Query;



$out = '';

if (isset ($_GET['start_year']) && isset ($_GET['start_month'])) {


  $start_year  = $_GET['start_year'];
  $start_month = $_GET['start_month'];

  $mode = 1; //отчет за месяц

  if (isset ($_GET['start_day'])) {
    $start_day = $_GET['start_day'];
    $mode      = 0; //отчет за день
  } else {
    $start_day = '01';
  }

  $mysql_start_date = "$start_year-$start_month-$start_day";
  $start_unixtime   = strtotime("$start_year-$start_month-$start_day");

  if (isset ($_GET['start_day'])) {
    $mysql_end_date = $mysql_start_date;
    $end_unixtime   = $start_unixtime;
  } else {
    $mysql_end_date = $start_year . '-' . $start_month . '-' . date('t', $start_unixtime);
    $end_unixtime   = strtotime($mysql_end_date);
  }


  if (checkdate($start_month, $start_day, $start_year)) {
    //обычные резервирования
    $q    = 'select
						if(r.client_id is null,md5(concat(r.client_name,r.client_surname)),c.client_id) as client_id,
						if(r.client_id is null,r.client_name,c.name) as name,
						if(r.client_id is null,r.client_surname,c.surname)  as surname,
						at.type_id,
						at.title as type,
						a.area_id,
						a.title as area,
						st.code, sp.code as sprice, r.memo,
						r.start, substring(r.finish,12,5) as finish,
						r.price,
						if(r.light_state="1", r.light_price, 0.00) as light_price,
						if(r.heating_state="1", r.heating_price, 0.00) as heating_price,
						r.encash, 
						if(c.mode is null,2,c.mode - 1) as mode
					from ' . Query::tableName('reservations') . ' r
					left join ' . Query::tableName('areas') . ' a on a.area_id = r.area_id
					left join ' . Query::tableName('areas_types') . ' at on at.type_id = a.type_id
					left join ' . Query::tableName('clients') . ' c on r.client_id = c.client_id
					left join ' . Query::tableName('reservations_stocks') . ' st on r.stock_id = st.stock_id
					left join ' . Query::tableName('reservations_specprice') . ' sp on r.sprice_id = sp.sprice_id
					where "' . $mysql_start_date . '" <= substring(start,1,10) and "' . $mysql_end_date . '" >= substring(start,1,10)' . ((isset($type_id) && $type_id != 0)
        ? ' and at.type_id = ' . $type_id : '');
    $temp = Query::sqlQuery($q);
    foreach ($temp as $r) {
      if (!isset ($clients[$r['client_id']])) {
        $clients[$r['client_id']] = array($r['surname'] . ' ' . $r['name'], $r['mode']);
      }
      $periods[$r['type']][substr($r['start'], 0, 10)][$r['area_id'] . '#' . substr($r['start'], 11)] = array(
        $r['area'] . (empty($r['code']) ? ''
          : ' [' . $r['code'] . ']') . (empty($r['sprice']) ? '' : ' <strong>[' . $r['sprice'] . ']</strong>') . ($r['encash'] == 2
          ? ' <strong>GH</strong>' : '') . (empty($r['memo']) ? '' : ' <br/>[' . $r['memo'] . ']'),
        $r['finish'],
        $r['client_id'],
        $r['price'],
        ($r['encash'] == 1 ? 1 : ($r['encash'] == 3 ? 3 : 0)),
        $r['light_price'],
        $r['heating_price']
      );
    }

    /*	print_r($periods);*/
    require '../config.php';

    $out .= "<div id=\"main\">";
    $out .= "<div id=\"top\">\n";
    $out .= "<h1>" . config('app')->getProjectTitle() . "<br>" . (isset($type_title) ? $type_title . '<br>'
        : '') . "<b>" . $start_day . "/" . $start_month . "/" . $start_year . " </b></h1>\n";
    $out .= "</div>\n";

    $price_overall = 0;

    $out .= drawDateDivs($mode);

    $out .= "</div>\n";
  } else {
    $out = 'Incorrect date';
  }
} else {
  $out = 'Date not presented';
}

$_page['content'][0] = $out;
$_page['key']        = 'reservations_report';



function drawDateDivs($mode)
{
  $out = '';
  if (is_array($GLOBALS['periods'])) {
    foreach ($GLOBALS['periods'] as $type_title => $data) {
      $out .= "<div class=\"typeTitle\">" . $type_title . "</div>\n";

      $mreh_sum = $mbar_sum = 0;

      if (is_array($data)) {
        ksort($data);
        foreach ($data as $date => $reservation) {
          $reh_sum = $bar_sum = $pp_sum = 0;

          ksort($reservation);
          $out .= "<div class=\"client\">\n<div class=\"title\">";
          $out .= date("d.m.Y", strtotime($date));
          $out .= "</div>\n";
          $out .= "<div class=\"periods\">\n";
          $out .= "<table cellspacing=\"0\" class=\"periods\">\n";

          if (is_array($reservation)) {
            foreach ($reservation as $start => $tmp) {
              $out .= "<tr><td class=\"a\" style=\"width:10%\">" . $tmp[0] . "</td><td class=\"p\" style=\"width:20%\">" . date(
                  'H:i',
                  strtotime(
                    substr($start, -8)
                  )
                ) . " - " . $tmp[1] . "</td><td class=\"a\" style=\"width:30%\">" . $GLOBALS['clients'][$tmp[2]][0] . ' (' . ($GLOBALS['clients'][$tmp[2]][1]
                  ? 'Offline-Kunden' : 'Online-Kunden') . ')' . "</td><td style=\"width:20%\" class=\"p\">" . ((isset ($tmp[4]) && $tmp[4] == 0)
                  ? 'BR' : ((isset ($tmp[4]) && $tmp[4] == 3) ? 'PP' : 'RECHNUNG')) . "</td><td style=\"width:20%\">" . (isset ($tmp[3])
                  ? number_format($tmp[3], 2, ',', ' ') . ' <span style="color:blue">L:</span> ' . number_format(
                    $tmp[5],
                    2,
                    ',',
                    ' '
                  ) . ' <span style="color:blue">H:</span> ' . number_format($tmp[6], 2, ',', ' ') . ' ' . CURR_VALUTE : 'Abo') . "</td></tr>\n";
              if (isset ($tmp[4]) && $tmp[4] == 0) {
                $bar_sum += (isset ($tmp[3]) ? $tmp[3] : 0);
              } elseif (isset ($tmp[4]) && $tmp[4] == 3) {
                $pp_sum += (isset ($tmp[3]) ? $tmp[3] : 0);
              } else {
                $reh_sum += (isset ($tmp[3]) ? $tmp[3] : 0);
              }
            }
          }

          $mbar_sum += $bar_sum;
          $mreh_sum += $reh_sum;
          $mpp_sum  += $pp_sum;

          $out .= "</table>\n";
          $out .= '<p style="text-align:right"><strong>';
          $out .= 'Barsumme heute: ' . number_format($bar_sum, 2, ',', '') . " " . CURR_VALUTE . "<br />\n";
          $out .= 'Rechnungssume heute: ' . number_format($reh_sum, 2, ',', '') . " " . CURR_VALUTE . "<br /> \n";
          $out .= 'PP heute: ' . number_format($pp_sum, 2, ',', '') . " " . CURR_VALUTE . "<br />\n";
          $out .= '</strong></p>';
          $out .= "</div>\n</div>\n\n";
        }
        if ($mode == 1) {
          $out .= '<p style="text-align:right"><strong>';
          $out .= 'Barsumme kumuliert Monat: ' . number_format($mbar_sum, 2, ',', '') . " " . CURR_VALUTE . "<br />\n";
          $out .= 'Rechnungssumme kumuliert Monat: ' . number_format($mreh_sum, 2, ',', '') . " " . CURR_VALUTE . "<br />\n";
          $out .= 'PP kumuliert Monat: ' . number_format($mpp_sum, 2, ',', '') . " " . CURR_VALUTE . "\n";
          $out .= '</strong></p>';
        }
      }
    }
  }

  return $out;
}