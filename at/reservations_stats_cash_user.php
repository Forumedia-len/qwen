<?

use AC\core\system\db\Query;


$out = '';
$hasCsv = Service::request()->_get('action') == 'CSV';
if (isset ($_GET['year']) && isset ($_GET['month'])) {
  //запрашиваем название типа площадок
  
  $year  = $_GET['year'];
  $month = $_GET['month'];
  
  $mysql_date = "$year-$month-01";
  $unixtime   = strtotime("$year-$month-01");
  
  $out = "<div id=\"main\">";
  $out .= "<div id=\"top\">\n";
  $out .= "<h1>" . config('app')->getProjectTitle() . "<br>" . (isset($type_title) ? $type_title . '<br>' : '') . "<b>" . date('Y',
      $unixtime) . " / " . date(
      'm',
      $unixtime
    ) . "</b></h1>\n";
  $out .= "</div>\n";
  
  if (date('Y-m-d', $unixtime) == $mysql_date) {
    $temp2 = Query::sqlQuery(
      'SELECT client_id, login, name, surname, prepayment_sum FROM ' . Query::tableName('clients') . ' where prepayment_sum > 0 ORDER BY name'
    );
    if (!empty($temp2)) {
      foreach ($temp2 as $row) {
        $data[$row['client_id']] = [
          'login'          => $row['login'],
          'name'           => $row['name'],
          'surname'        => $row['surname'],
          'prepayment_sum' => $row['prepayment_sum']
        ];
      }
    }
    
    $temp = Query::sqlQuery(
      'SELECT cl.client_id, cl.login, cl.name, cl.surname, arp.price, cl.prepayment_sum 
			FROM ' . Query::tableName('clients') . ' cl 
			left join ' . Query::tableName('accounts_clients') . ' acc ON acc.sys_client_id = cl.client_id 
			left join ' . Query::tableName('accounts') . ' a ON a.client_id = acc.client_id 
			left join ' . Query::tableName('accounts_reservations_prepayment') . ' arp ON arp.account_id = a.account_id 
			WHERE a.account_type="3" AND a.execution="1" AND a.deleted="0" AND  (a.date_start BETWEEN "' . date('Y', $unixtime) . '-01-01" AND "' . date(
        'Y-m',
        $unixtime
      ) . '-' . date('t', $unixtime) . '") 
			GROUP BY a.account_id ORDER BY cl.name'
    );
    
    if (!empty($temp)) {
      foreach ($temp as $row) {
        if (isset($data[$row['client_id']]['income'])) {
          $tmp = $data[$row['client_id']]['income'] + $row['price'];
        } else {
          $tmp = $row['price'];
        }
        $data[$row['client_id']]           = [
          'login'          => $row['login'],
          'name'           => $row['name'],
          'surname'        => $row['surname'],
          'prepayment_sum' => $row['prepayment_sum']
        ];
        $data[$row['client_id']]['income'] = $tmp;
      }
    }
    
    $temp = Query::sqlQuery(
      'SELECT cl.client_id, SUM(r.price) AS outcome FROM ' . Query::tableName('clients') . ' cl 
									LEFT JOIN ' . Query::tableName('reservations') . ' r ON r.client_id = cl.`client_id`
									WHERE (r.start BETWEEN "' . date('Y', $unixtime) . '-01-01"  AND "' . date('Y-m', $unixtime) . '-' . date('t', $unixtime) . '" ) AND r.encash = "2" 
									GROUP BY cl.client_id ORDER BY cl.name'
    );
    
    if (!empty($temp)) {
      foreach ($temp as $row) {
        if (isset($data[$row['client_id']])) {
          $data[$row['client_id']]['outcome'] = $row['outcome'];
        }
      }
    }
    
    
    if (isset($data)) {
      $out .= "<div class=\"periods\">\n";
      $out .= "<table cellspacing=\"0\" class=\"periods\">\n";
      $out .= "<tr><th>Benutzername</th><th>Vorname</th><th>Familienname</th><th>Zahlungszugang " . date(
          'Y',
          $unixtime
        ) . "</th><th>Zahlungsabgang " . date('Y', $unixtime) . "</th><th>Restguthaben aus " . date(
          'Y',
          $unixtime
        ) . "</th><th>Aktuelles GESAMTGUTHABEN</th></tr>\n";
      $csv = 'Benutzername;Vorname;Familienname;Zahlungszugang ' . date('Y', $unixtime) . ';Zahlungsabgang ' . date(
          'Y',
          $unixtime
        ) . ';Restguthaben aus ' . date('Y', $unixtime) . ';Aktuelles GESAMTGUTHABEN' . "\n";
      foreach ($data as $row) {
        if ($hasCsv) {
          $csv .= $row['login'] . ';' . $row['name'] . ';' . $row['surname'] . ';' . number_format($row['income'], 2, ',', '') . ';' . number_format(
              $row['outcome'],
              2,
              ',',
              ''
            ) . ';' . number_format(($row['income'] - $row['outcome']), 2, ',', '') . ';' . number_format($row['prepayment_sum'], 2, ',', '') . "\n";
        } else {
          $out .= "<tr>
							<td class=\"a\" style=\"width:10%\">" . $row['login'] . "</td>
							<td class=\"p\" style=\"width:15%; text-align:left;\">" . $row['name'] . "</td>
							<td class=\"a\" style=\"width:15%\" >" . $row['surname'] . "</td>
							<td class=\"p\" style=\"width:15%;  text-align:right;\">" . number_format($row['income'], 2, ',', '') . " " . CURR_VALUTE . "</td>
							<td class=\"a\" style=\"width:15%;  text-align:right;\">" . number_format($row['outcome'], 2, ',', '') . " " . CURR_VALUTE . "</td>
							<td class=\"p\" style=\"width:15%;  text-align:right;\">" . number_format(
              ($row['income'] - $row['outcome']),
              2,
              ',',
              ''
            ) . " " . CURR_VALUTE . "</td>
							<td class=\"a\" style=\"width:15%; text-align:right;\">" . number_format(
              $row['prepayment_sum'],
              2,
              ',',
              ''
            ) . " " . CURR_VALUTE . "</td></tr>\n";
        }
      }
      $out .= "</table>\n";
      
      $out .= "</div>\n</div>\n\n";
    }
  } else {
    $out = 'Date not presented';
  }
  $out .= "</div><br /><br />\n";
} else {
  $out = 'Incorrect data';
}

if ($hasCsv) {
  header("Content-Disposition: attachment; filename=report_export_" . date('Y_m_d') . ".csv");
  header("Content-Type: application/x-force-download; name=\"report_export_" . date('Y_m_d') . ".csv\"");
  echo utf8_decode($csv);
  die;
}

$_page['content'][0] = $out;
$_page['key']        = 'report_private_account_clients';
