<?php

use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TranslateHelper;

$r = Service::engines();
$_page['key']        = 'clients_reservations';
$_page['title']      = lang('title',
    'clients_reservations') . ' ' . $r->clients->current_client_data['name'] . ' ' . $r->clients->current_client_data['surname'];
$_page['meta-title'] = lang('title', 'clients_reservations');

$out = '<div class="aroundBox">' . "\n";
$out .= '	<div class="content fm-client-reservations">' . "\n";
if (!empty($r->clients->current_client_data['client_id']) && $r->getReservationDataByClient($r->clients->current_client_data['client_id'], null,
    $reservation_data)) {
  $out .= '<div class="table-adaptive" tabindex="0" role="region" aria-label="'
    . StringHelper::shield(lang('title', 'clients_reservations')) . '">';
  $out .= '<table class="clientReservations fm-client-bookings">' . "\n";
  $out .= '<tr>' . "\n";
  $out .= '<th>' . lang('date') . '</th>' . "\n";
  $out .= '<th>' . lang('time') . '</th>' . "\n";
  $out .= '<th>' . lang('type') . '</th>' . "\n";
  $out .= '<th>' . lang('booking_options', 'clients_reservations') . '</th>' . "\n";
  $out .= '<th>' . lang('special_price', 'clients_reservations') . '</th>' . "\n";
  $out .= '<th>' . lang('payment_method', 'clients_reservations') . '</th>' . "\n";
  $out .= '<th>' . lang('booking_text', 'clients_reservations') . '</th>' . "\n";
  $out .= '</tr>' . "\n";
  $i   = 0;
  
  foreach ($reservation_data as $reservation) {
    $out    .= '<tr ' . (($i % 2 == 0) ? '' : 'class="dark"') . '>' . "\n";
    $out    .= '<td>' . date('d.m.Y', strtotime($reservation['start'])) . '</td>' . "\n";
    $out    .= '<td>' . date('H:i', strtotime($reservation['start'])) . ' - ' . date('H:i',
        strtotime($reservation['finish'])) . '</td>' . "\n";
    $out    .= '<td><p>' . str_replace(' - ', '</p><p>', $r->areas->getTitleByAreaId($reservation['area_id'], 'title_site_url', true)) . '</p></td>' . "\n";
    $stocks = !empty($reservation['stocks']) ? array_keys($reservation['stocks']) : (array)$reservation['stock_id'];
    if (empty($stocks)) {
      $out .= '<td>&nbsp;</td>' . "\n";
    } else {
      $out .= '<td style="text-align: left">';
      foreach ($stocks as $stock_id) {
        if ($r->stocks->getStock($stock_id, $stock)) {
          $out .= '<p>[' . $stock['code'] . '] ' . $stock['title'] . ' (' . NumberHelper::format($stock['rate']) . ' ' . ($stock['dimension'] == 2 ? ' % ' : CURR_VALUTE) . ')</p>';
        }
      }
      $out .= '</td>' . "\n";
    }
    
    if (empty($reservation['sprice_id'])) {
      $out .= '<td>&nbsp;</td>' . "\n";
    } else {
      $r->specprice->getSprice($reservation['sprice_id'], $sp);
      $out .= '<td>[' . $sp['code'] . '] ' . $sp['title'] . ' (' . NumberHelper::valute($sp['rate']) . ')</td>' . "\n";
    }
    
    $out .= '<td>' . $r->getTitleEncash($reservation['encash']) . '</td>' . "\n";
    
    $out .= '<td>' . (!empty($reservation['memo']) ? $reservation['memo'] : '&nbsp;') . '</td>' . "\n";
    $out .= '</tr>' . "\n";
    $i++;
  }
  $out .= '</table>' . "\n";
  $out .= '</div>';
}

if ($tickets = $r->tickets->getTicketsClients($r->clients->current_client_data['client_id'])) {
  $out .= '<br>' . lang('abos', 'clients_reservations') . "\n";
  $out .= '<div class="table-adaptive" tabindex="0" role="region" aria-label="'
    . StringHelper::shield(lang('abos', 'clients_reservations')) . '">';
  $out .= '<table class="clientReservations fm-client-subscriptions">' . "\n";
  $out .= '<tr>' . "\n";
  
  $out .= '<th>' . lang('time') . '</th>' . "\n";
  $out .= '<th>' . lang('weekdays') . '</th>' . "\n";
  $out .= '<th>' . lang('date') . '</th>' . "\n";
  $out .= '<th>' . lang('type') . '</th>' . "\n";
  $out .= '<th>' . lang('place') . '</th>' . "\n";
  $out .= '</tr>' . "\n";
  $i   = 0;
  foreach ($tickets as $ticket) {
    $out      .= '<tr ' . (($i % 2 == 0) ? '' : 'class="dark"') . '>' . "\n";
    $out      .= '<td>' . date('H:i', strtotime($ticket['time_start'])) . ' - ' . date('H:i',
        strtotime($ticket['time_finish'])) . '</td>' . "\n";
    $out      .= '<td>' . "\n";
    $weekdays = explode(',', $ticket['weekdays']);
    foreach ($weekdays as $w) {
      $out .= TranslateHelper::translateWeekday($w) . '<br>';
    }
    $out .= '</td>' . "\n";
    $out .= '<td>' . date('d.m.Y', strtotime($ticket['start'])) . ' - ' . date('d.m.Y',
        strtotime($ticket['finish'])) . '</td>' . "\n";
    $out .= '<td>' . $r->areas->getTitleByAreaId($ticket['area_id'], 'title_site_url') . '</td>' . "\n";
    $out .= '<td>' . $ticket['area_title'] . '</td>' . "\n";
    
    $out .= '</tr>' . "\n";
    $i++;
  }
  $out .= '</table>' . "\n";
  $out .= '</div>';
}

$out .= '	</div>' . "\n";
$out .= '</div>' . "\n";

$_page['content'][1] = $out;
