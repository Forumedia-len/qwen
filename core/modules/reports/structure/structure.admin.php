<?php

use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\services\OnlineGatewayService;

$admin = [];
//отчеты
$admin['reports']['parent_key'] = 'login';
$admin['reports']['template']   = 'internal';
$admin['reports']['title']      = lang('reservations_reports_title', 'structure');
$admin['reports']['icon']       = 'invoice';
$admin['reports']['href']       = 'reports';
$admin['reports']['sort']       = 120;

//$admin['reports_reservations']['parent_key'] = 'reports';
//$admin['reports_reservations']['template']   = 'internal';
//$admin['reports_reservations']['title']      = lang('reservations_reports_common_title', 'structure');
//$admin['reports_reservations']['href']       = 'reports/reservations';
//$admin['reports_reservations']['sort']       = 100;

//statistics
$admin['report']['parent_key'] = 'reports';
$admin['report']['template']   = 'statistics';
$admin['report']['visible']    = false;
$admin['report']['sort']       = 200;

$admin['report_reservations_for_day'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_day.php',
    'href'       => 'reports/view/report/reservations_for_day',
    'title'      => lang('Day Report', 'reports'),
    'visible'    => false,
    'sort'       => 210
  ];

$admin['report_reservations_for_month'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_month.php',
    'href'       => 'reports/view/report/reservations_for_month',
    'title'      => lang('Monthly report', 'reports'),
    'visible'    => false,
    'sort'       => 220
  ];

$admin['report_reservations_for_period'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_period.php',
    'href'       => 'reports/view/report/reservations_for_period',
    'title'      => lang('Periodic report', 'reports'),
    'visible'    => false,
    'sort'       => 230
  ];

$admin['report_reservations_as_table'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_orders_table.php',
    'href'       => 'reports/view/report/reservations_as_table',
    'title'      => lang('Game schedule overview', 'reports'),
    'visible'    => false,
    'sort'       => 240
  ];

$admin['report_reservations_export'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_export.php',
    'href'       => 'reports/view/report/reservations_export',
    'title'      => lang('Export', 'reports'),
    'visible'    => false,
    'sort'       => 250
  ];

$admin['report_private_account_clients'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_cash_user.php',
    'href'       => 'reports/view/report/private_account_clients',
    'title'      => lang('Overview customer balances', 'reports'),
    'visible'    => false,
    'sort'       => 260
  ];

$admin['report_payment_pay_online'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_period_by_data_reservation.php',
    'href'       => 'reports/view/report/payment_pay_online',
    'title'      => lang((OnlineGatewayService::runtimeGateway() === OnlineGateway::PAYONE->value ? 'Payone' : 'Paypal') . '-Payments at the time of payment', 'reports'),
    'visible'    => false,
    'sort'       => 270
  ];


// Внутренний магазин и статистика по наличке
$admin['report_reservations_for_day_bar'] =
  [
    'parent_key' => 'reports',
    'template'   => 'statistics',
//    'href'       => 'reservations_stats_day_bar.php',
    'href'       => 'reports/view/report/reservations_for_day_bar',
    'title'      => lang('Cash report', 'reports'),
    'visible'    => false,
    'sort'       => 900
  ];

return $admin;