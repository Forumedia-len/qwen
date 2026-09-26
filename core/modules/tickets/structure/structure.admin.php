<?php

$admin = [];

//билеты
$admin['tickets_']['parent_key'] = 'login';
$admin['tickets_']['template']   = 'internal';
$admin['tickets_']['title']      = lang('tickets_title', 'structure');
$admin['tickets_']['icon']       = 'ticket';
//$admin['tickets_']['href']       = 'tickets';
$admin['tickets_']['href']       = 'tickets.php';
$admin['tickets_']['sort']       = 180;

$admin['tickets']['parent_key'] = 'tickets_';
$admin['tickets']['template']   = 'internal';
$admin['tickets']['title']      = lang('tickets_view_title', 'structure');
//$admin['tickets']['href']       = 'tickets';
$admin['tickets']['href']       = 'tickets.php';
$admin['tickets']['tab']       = 'tickets';
$admin['tickets']['sort']       = 100;

$admin['tickets_show']['parent_key'] = 'tickets';
$admin['tickets_show']['template']   = 'internal';
$admin['tickets_show']['title']      = lang('tickets_view_title', 'structure');
//$admin['tickets_show']['href']       = 'tickets';
$admin['tickets_show']['href']       = 'tickets.php';
$admin['tickets_show']['sort']       = 100;

$admin['tickets_export']['parent_key'] = 'tickets';
$admin['tickets_export']['template']   = 'internal';
$admin['tickets_export']['title']      = lang('tickets_export_title', 'structure');
//$admin['tickets_export']['href']       = 'tickets/export';
$admin['tickets_export']['href']       = 'tickets.php?action=viewExportFormTickets';
$admin['tickets_export']['sort']       = 200;

return $admin;