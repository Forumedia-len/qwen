<?php

use AC\core\modules\reports\engines\ReportsEngine;
use AC\core\system\db\Query;

use AC\core\engines\Engines;

$engine = new Engines();

$type_id      = (int)Service::request()->_('type_id');
$areas_prices = [];
$out          = '';
$error        = false;
$fields       = [
  'title_row' => 2,
  'fields'    => [
    'ordered_date' => ['row' => 1, 'col' => 1, 'title' => lang('Date'), 'parent' => lang('Booking date', 'reports')],
    'ordered_time' => ['row' => 1, 'col' => 1, 'title' => lang('Time'), 'parent' => lang('Booking date', 'reports')],
    'pay_state'    => ['row' => 1, 'col' => 1, 'title' => 'PayOne Reference', 'parent' => 'Buchungsdatum'],
    'surname'      => ['row' => 1, 'col' => 1, 'title' => 'Familienname', 'parent' => 'Kunde'],
    'name'         => ['row' => 1, 'col' => 1, 'title' => 'Vorname', 'parent' => 'Kunde'],
    'email'        => ['row' => 1, 'col' => 1, 'title' => 'E-Mail', 'parent' => 'Kunde'],
    'registered'   => ['row' => 1, 'col' => 1, 'title' => 'Registriert am', 'parent' => 'Kunde'],
    'area_type'    => ['row' => 1, 'col' => 1, 'title' => lang('Type'), 'parent' => lang('Reservations', 'reports')],
    'area'         => ['row' => 1, 'col' => 1, 'title' => lang('Place'), 'parent' => lang('Reservations', 'reports')],
    'date'         => ['row' => 1, 'col' => 1, 'title' => lang('Date'), 'parent' => lang('Reservations', 'reports')],
    'time'         => ['row' => 1, 'col' => 1, 'title' => lang('Time period'), 'parent' => lang('Reservations', 'reports')],
    'pay_one_type' => ['row' => 1, 'col' => 1, 'title' => lang('Payment methods'), 'parent' => lang('Reservations', 'reports')],
    'gh_number'    => ['row' => 1, 'col' => 1, 'title' => 'GH Rechnungsnummer', 'parent' => 'Reservierungen'],
    'instruction'  => ['row' => 1, 'col' => 1, 'title' => 'Hinweis', 'parent' => 'Reservierungen'],
    'price'        => ['row' => 1, 'col' => 1, 'title' => lang('Price'), 'parent' => lang('Reservations', 'reports')],
  ]
];

//$fields       = [
//  'title_row' => 2,
//  'fields'    => [
//    'ordered_date' => ['row' => 1, 'col' => 1, 'title' => 'Datum', 'parent' => 'Buchungsdatum'],
//    'ordered_time' => ['row' => 1, 'col' => 1, 'title' => 'Zeit', 'parent' => 'Buchungsdatum'],
//    'pay_state'    => ['row' => 1, 'col' => 1, 'title' => 'PayOne Reference', 'parent' => 'Buchungsdatum'],
//    'surname'      => ['row' => 1, 'col' => 1, 'title' => 'Familienname', 'parent' => 'Kunde'],
//    'name'         => ['row' => 1, 'col' => 1, 'title' => 'Vorname', 'parent' => 'Kunde'],
//    'email'        => ['row' => 1, 'col' => 1, 'title' => 'E-Mail', 'parent' => 'Kunde'],
//    'registered'   => ['row' => 1, 'col' => 1, 'title' => 'Registriert am', 'parent' => 'Kunde'],
//    'area_type'    => ['row' => 1, 'col' => 1, 'title' => 'Typ', 'parent' => 'Reservierungen'],
//    'area'         => ['row' => 1, 'col' => 1, 'title' => 'Platz', 'parent' => 'Reservierungen'],
//    'date'         => ['row' => 1, 'col' => 1, 'title' => 'Datum', 'parent' => 'Reservierungen'],
//    'time'         => ['row' => 1, 'col' => 1, 'title' => 'Zeitraum', 'parent' => 'Reservierungen'],
//    'pay_one_type' => ['row' => 1, 'col' => 1, 'title' => 'Zahlungsarten', 'parent' => 'Reservierungen'],
//    'gh_number'    => ['row' => 1, 'col' => 1, 'title' => 'GH Rechnungsnummer', 'parent' => 'Reservierungen'],
//    'instruction'  => ['row' => 1, 'col' => 1, 'title' => 'Hinweis', 'parent' => 'Reservierungen'],
//    'price'        => ['row' => 1, 'col' => 1, 'title' => 'Preis, €', 'parent' => 'Reservierungen'],


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
    $rr   = new ReportsEngine();
    $data = $rr->getPayOneReports($mysql_start_date, $mysql_end_date);
    
    ksort($data);
  } else {
    $error = true;
    $out   = lang('Incorrect date', 'message_error');
  }
} else {
  $error = true;
  $out   = lang('Date not presented', 'message_error');
}
if (!$error) {
  $action = Service::request()->_('action');
  if ($action == 'CSV') {
    $out .= showData($data, $fields, true);
    header("Content-Disposition: attachment; filename=stats_export.csv");
    header("Content-Type: application/x-force-download; name=\"stats_export.csv\"");
    echo $out;
    die;
  } else {
    $out .= "<div id=\"main-export\">";
    $out .= "<div id=\"top\">\n";
    $out .= "<h1>" . config('app')->getProjectTitle() . "<br>" . "<b>" . $start_day . "/" . $start_month . "/" . $start_year . " - " . $end_day . "/" . $end_month . "/" . $end_year . "</b></h1>\n";
    $out .= "</div>\n";
    $out .= showData($data, $fields, false);
    $out .= "</div>\n";
  }
}


$_page['content'][0] = $out;
$_page['key']        = 'report_payment_pay_online';


function showData($data, $fields, $csv = false)
{
  $full_price = 0;
  $out        = '';
  if (!$csv) {
    $out .= '<div class="periods">';
    $out .= '<table border="0" cellspacing="0" class="periods">';
  }
  $out .= !$csv ? '<tr>' : '';
  $out .= !$csv ? '<th rowspan="2">#</th>' : '';
  for ($row = 1; $row <= $fields['title_row']; $row++) {
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
  $j = 1;
  if (!empty($data)) {
    ksort($data);
    foreach ($data as $clients) {
      ksort($clients);
      foreach ($clients as $client) {
        foreach ($client as $game) {
          $out .= !$csv ? '<tr>' : '';
          $i   = 0;
          $out .= !$csv ? '<td>' . $j . '</td>' : '';
          foreach (array_keys($fields['fields']) as $field) {
            $value = $game[$field];
            if (in_array($field, ['price', 'light_price', 'heating_price'])) {
              $full_price += (float)$value;
              $value      = number_format($value, 2, ',', '');
            }
            $out .= $csv ? $value . ';' : '<td' . ($i % 2 == 1 ? ' class="pd"' : '') . '>' . $value . '</td>';
            $i++;
          }
          $out .= !$csv ? '</tr>' : "\n";
          $j++;
        }
      }
    }
  }
  
  if (!$csv) {
    $out .= '</table>';
    $out .= '</div>';
  }
  $out .= (!$csv
      ? '<div style="margin: 25px 0 25px 0; width: 98%; text-align: right;font-size: 16px; font-weight: bold">'
      : ';;;;;;;;;;;') . 'Gesamtbetrag: ' . (!$csv ? '' : ';') . number_format(
      $full_price,
      2,
      ',',
      ''
    ) . ' €' . (!$csv ? '</div>' : '');
  
  return ($csv ? "\xEF\xBB\xBF" : '') . $out;
}