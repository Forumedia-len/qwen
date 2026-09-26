<?php
header('Access-Control-Allow-Origin: *');
header('Content-type: text/xml; charset=utf-8');

$engine = Service::engines();

$mysql_date = date('Y-m-d');

$error = false;

$engine->getPeriodsByAreasTypeDate(1, null, $mysql_date, $areas_data, $error_code);
$engine->getPeriodsByAreasTypeDate(2, null, $mysql_date, $areas_data2, $error_code);



foreach ($areas_data2 as $key => $data) {
  $areas_data[$key] = $data;
}

//print_r($areas_data[12]);

$area_with_max_periods_period_count  = 0;
$area_with_max_periods_periods_array = array();
foreach ($areas_data as $area_id => $area_data) {
  if (isset($area_data[2])) {
    $period_count = count($area_data[2]);
    if ($period_count > $area_with_max_periods_period_count) {
      $area_with_max_periods_period_count  = $period_count;
      $area_with_max_periods_periods_array = $area_data[2];
    }
  }
}

foreach ($areas_data as $area_id => &$area_data2) {
  foreach ($area_with_max_periods_periods_array as $time => $periodR) {
    if (isset($area_data2[2][$time])) {
    } else {
      $area_data2[2][$time][0] = $periodR[0];
      $area_data2[2][$time][1] = "1000100";
    }
  }
  ksort($area_data2[2]);
}

if (is_array($areas_data)) {
  $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
  $xml .= "<periods date=\"" . date('d.m.Y') . "\">\n";
  foreach ($areas_data as $area_id => $area_data) {
    //перебираем прощадки
    if (!empty ($area_data[2])) {
      //день рабочий
      $p = 1;
      foreach ($area_data[2] as $period_start => $period) {

        //данные периода
        $xml .= "<period id=\"timeBox_" . $area_id . "_" . $p . "\" " .
          "start=\"" . $period_start . "\" finish=\"" . $period[0] . "\"" .
          " gone=\"" . (int)$period[1][0] . "\" blocked=\"" . (int)$period[1][1] . "\"" .
          " ordered=\"" . (int)$period[1][2] . "\"" .
          " order_status=\"" . (isset($period[1][7]) ? (int)$period[1][7] : 1) . "\"" .
          ' ticket="' . (int)$period[1][3] . '"' .
          ' light_state="' . (int)$period[1][4] . '"' .
          ' heating_state="' . (int)$period[1][5] . '"' .
          (isset($period[2]) && !empty($period[2]['memo']) ? ' memo="' . htmlspecialchars($period[2]['memo'], ENT_QUOTES) . '"' : '') .
          (isset($period[2]) && !empty($period[2]['customer']) ? ' customer="' . htmlspecialchars($period[2]['customer'], ENT_QUOTES) . '"' : '') .
          '>';

        if ($period[1][1] == 1) //информация о блокировке
        {
          $xml .= "<block reason=\"" . htmlspecialchars($period[2]['block'], ENT_QUOTES) . "\"/>";
        }

        if ($period[1][2] == 1 || $period[1][3] == 1) {
          //данные клиента
          $xml .= "<client " . (isset($period[2]) && $period[2]['client'][0] !== null ? "id=\"" . $period[2]['client'][0] . "\" " : "") . "name=\"" . ((!HIDE_NAME_ON_DISPLAY)?(htmlspecialchars(
              $period[2]['client'][1][0] . '. ' . $period[2]['client'][2],
              ENT_QUOTES
            )):'') . ($period[1][3] == 1 ? ' (Abo)' : '') . "\"/>";
          //данные абонемента
          if ($period[1][3] == 1) {
            $xml .= "<ticket " . ($period[2]['ticket_id'] !== null ? "id=\"" . $period[2]['ticket_id'] . "\" " : "") . "/>";
          }
          //данные акции
          if (isset($period[2]) && $period[2]['stock'] !== null) {
            $xml .= "<stock code=\"" . htmlspecialchars($period[2]['stock'], ENT_QUOTES) . "\"/>";
          }
        }

        $xml .= "</period>\n";
        $p++;
      }
    }
  }
  $xml .= "</periods>\n";
}

echo $xml;
?>