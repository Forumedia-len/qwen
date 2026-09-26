<?
//error_reporting (E_ALL);

//смотреть ZHS

use AC\core\system\db\Query;
use AC\core\system\helpers\TranslateHelper;
use AC\core\engines\Engines;


$out = '';

if (isset ($_GET['year_start']) && isset ($_GET['month_start']) && isset ($_GET['day_start']) &&
  isset ($_GET['year_finish']) && isset ($_GET['month_finish']) && isset ($_GET['day_finish'])) {


  $start_year  = $_GET['year_start'];
  $start_month = $_GET['month_start'];
  $start_day   = $_GET['day_start'];

  $mysql_start_date = "$start_year-$start_month-$start_day";
  $start_unixtime   = strtotime($mysql_start_date);

  $end_year  = $_GET['year_finish'];
  $end_month = $_GET['month_finish'];
  $end_day   = $_GET['day_finish'];

  if (isset($_GET['type_id'])) {
    $type_id = (int)$_GET['type_id'];

    $temp = Query::sqlQuery('select title from ' . Query::tableName('areas_types') . ' where type_id = ?', [(int)$type_id]);
    if (!empty($temp)) {
      $type_title = $temp[0]['title'];
    }
  }

  $mysql_end_date = "$end_year-$end_month-$end_day";
  $end_unixtime   = strtotime($mysql_end_date);
  $out            = '';
  if (checkdate($start_month, $start_day, $start_year) && checkdate($end_month, $end_day, $end_year) && $end_unixtime > $start_unixtime) {
    $r = new Engines();
    if ($r->getPeriodsByAreasTypeDatePeriod($type_id, null, $mysql_start_date, $mysql_end_date, $out_data, $error_code)) {
      $tmp = array();
      foreach ($out_data as $day => $areas) {
        $month = TranslateHelper::translateMonth(date('m', strtotime($day))) . ' ' . date('Y', strtotime($day));
        if (!isset($tmp[$month])) {
          $tmp[$month] = array('all' => 0, 'rain' => 0, 'watering' => 0, 'order' => 0, 'fakultat' => 0, 'sport' => 0, 'uni' => 0, 'privater' => 0);
        }

        foreach ($areas as $area_id => $periods) {
          foreach ($periods as $st => $period) {
            switch ($period[1]) {
              case ($period[1][1] == '1'):
                if ($period[2]['block'][1] == 1) {  //дождь
                  $tmp[$month]['rain']++;
                } elseif ($period[2]['block'][1] == 2) {  //Полив
                  $tmp[$month]['watering']++;
                }
                break;
              case ($period[1][2] == '1'):
                if ($period[2]['client'][4] == '1') {  //Fakultät
                  $tmp[$month]['fakultat']++;
                } elseif ($period[2]['client'][4] == '2') {  //Hochschul-sport
                  $tmp[$month]['sport']++;
                } elseif ($period[2]['client'][4] == '3') {  //Uni
                  $tmp[$month]['uni']++;
                } elseif ($period[2]['client'][4] == '4') {  //privater Unterricht
                  $tmp[$month]['privater']++;
                } else {
                  $tmp[$month]['order']++;
                }
                break;
              case ($period[1][3] == '1'):
                if ($period[2]['client'][4] == '1') {  //Fakultät
                  $tmp[$month]['fakultat']++;
                } elseif ($period[2]['client'][4] == '2') {  //Hochschul-sport
                  $tmp[$month]['sport']++;
                } elseif ($period[2]['client'][4] == '3') {  //Uni
                  $tmp[$month]['uni']++;
                } elseif ($period[2]['client'][4] == '4') {  //privater Unterricht
                  $tmp[$month]['privater']++;
                } else {
                  $tmp[$month]['order']++;
                }
                break;
            }
            $tmp[$month]['all']++;
          }
        }
      }

      $tmp30 = $tmp;
      $tmp   = array();
      //получаем часы
      foreach ($tmp30 as $title => $val) {
        foreach ($val as $k => $v) {
          $tmp[$title][$k] = $v / 2;
        }
      }

      $out       .= '<div id="report-block">';
      $out       .= '<br /><p style="text-align:right"><strong>Stand: ' . date('d.m.Y') . '</strong></p><br />';
      $out       .= '<table>';
      $out       .= '<tr>';
      $out       .= '<th>&nbsp;</th>';
      $out       .= '<th class="blue">mögliche Belegung brutto</th>';
      $out       .= '<th class="blue">Bewässerung</th>';
      $out       .= '<th class="blue">Regen</th>';
      $out       .= '<th class="blue">mögliche Belegung netto</th>';
      $out       .= '<th class="yellow">Spielbare Stunden brutto</th>';
      $out       .= '<th class="orange">Fakultät</th>';
      $out       .= '<th class="orange">Hochschul-sport</th>';
      $out       .= '<th class="orange">Freies Spiel</th>';
      $out       .= '<th class="orange">Uni</th>';
      $out       .= '<th class="orange">privater Unterricht</th>';
      $out       .= '<th class="orange">tatsächlich gespielte Stunden</th>';
      $out       .= '<th class="orange">tatsächliche Belegung in Prozent</th>';
      $out       .= '</tr>';
      $tmp_total = array('all' => 0, 'rain' => 0, 'watering' => 0, 'order' => 0, 'fakultat' => 0, 'sport' => 0, 'uni' => 0, 'privater' => 0);
      foreach ($tmp as $title => $val) {
        $out                   .= '<tr>';
        $out                   .= '<td>' . $title . '</td>';
        $out                   .= '<td>' . $val['all'] . '</td>';
        $out                   .= '<td>' . $val['watering'] . '</td>';
        $out                   .= '<td>' . $val['rain'] . '</td>';
        $out                   .= '<td>' . ($val['all'] - $val['rain'] - $val['watering']) . '</td>';
        $out                   .= '<td>' . $val['all'] . '</td>';
        $out                   .= '<td>' . $val['fakultat'] . '</td>';
        $out                   .= '<td>' . $val['sport'] . '</td>';
        $out                   .= '<td>' . $val['order'] . '</td>';
        $out                   .= '<td>' . $val['uni'] . '</td>';
        $out                   .= '<td>' . $val['privater'] . '</td>';
        $order_sum             = ($val['fakultat'] + $val['order'] + $val['sport'] + $val['uni'] + $val['privater']);
        $out                   .= '<td>' . $order_sum . '</td>';
        $out                   .= '<td>' . number_format(($order_sum * 100 / $val['all']), 2, ',', '') . '%</td>';
        $out                   .= '</tr>';
        $tmp_total['all']      += $val['all'];
        $tmp_total['rain']     += $val['rain'];
        $tmp_total['watering'] += $val['watering'];
        $tmp_total['fakultat'] += $val['fakultat'];
        $tmp_total['sport']    += $val['sport'];
        $tmp_total['order']    += $val['order'];
        $tmp_total['uni']      += $val['uni'];
        $tmp_total['privater'] += $val['privater'];
      }
      $out       .= '<tr>';
      $out       .= '<th>Gesamtstunden</th>';
      $out       .= '<th class="blue text-default">' . $tmp_total['all'] . '</th>';
      $out       .= '<th class="blue text-default">' . $tmp_total['watering'] . '</th>';
      $out       .= '<th class="blue text-default">' . $tmp_total['rain'] . '</th>';
      $out       .= '<th class="blue text-default">' . ($tmp_total['all'] - $tmp_total['watering'] - $tmp_total['rain']) . '</th>';
      $out       .= '<th class="yellow text-default">' . $tmp_total['all'] . '</th>';
      $out       .= '<th class="orange text-default">' . $tmp_total['fakultat'] . '</th>';
      $out       .= '<th class="orange text-default">' . $tmp_total['sport'] . '</th>';
      $out       .= '<th class="orange text-default">' . $tmp_total['order'] . '</th>';
      $out       .= '<th class="orange text-default">' . $tmp_total['uni'] . '</th>';
      $out       .= '<th class="orange text-default">' . $tmp_total['privater'] . '</th>';
      $order_sum = ($tmp_total['fakultat'] + $tmp_total['order'] + $tmp_total['sport'] + $tmp_total['uni'] + $tmp_total['privater']);
      $out       .= '<th class="orange text-default">' . $order_sum . '</th>';
      $out       .= '<th class="orange text-default">' . number_format(($order_sum * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '</tr>';
      $out       .= '<tr>';
      $out       .= '<th>Anteile in %</th>';
      $out       .= '<th class="blue text-default">100%</th>';
      $out       .= '<th class="blue text-default">' . number_format(($tmp_total['watering'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="blue text-default">' . number_format(($tmp_total['rain'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="blue text-default">' . number_format(
          (($tmp_total['all'] - $tmp_total['watering'] - $tmp_total['rain']) * 100 / $tmp_total['all']),
          2,
          ',',
          ''
        ) . '%</th>';
      $out       .= '<th class="yellow text-default">100%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($tmp_total['fakultat'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($tmp_total['sport'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($tmp_total['order'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($tmp_total['uni'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($tmp_total['privater'] * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange text-default">' . number_format(($order_sum * 100 / $tmp_total['all']), 2, ',', '') . '%</th>';
      $out       .= '<th class="orange">&nbsp;</th>';
      $out       .= '</tr>';
      $out       .= '</table>';
      $out       .= '</div>';
    }
  } else {
    $out = 'Incorrect date';
  }
} else {
  $out = 'Date not presented';
}

$out .= '<style>';
$out .= '#report-block {width:70%; margin:0 auto;}';
$out .= '#report-block table{border-collapse:collapse; width:100%;}';
$out .= '#report-block table th{border:1px dotted #217346; padding:5px; text-align:center;}';
$out .= '#report-block table th.blue{background-color:#ddebf7; color:#002060;}';
$out .= '#report-block table th.yellow{background-color:#ffe699; color:#ca0000;}';
$out .= '#report-block table th.orange{background-color:#f8cbad; color:#ca0000;}';
$out .= '#report-block table tr th:first-child, #report-block table tr td:first-child{text-align:left;}';
$out .= '#report-block table th.text-default{color:#000000;}';
$out .= '#report-block table td{border:1px dotted #808080; padding:5px; text-align:center;}';
$out .= '</style>';

$_page['content'][0] = $out;
$_page['key']        = 'reservations_report';
$_page['title']      = 'Statistik';

