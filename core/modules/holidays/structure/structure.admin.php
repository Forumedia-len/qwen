<?php

$admin = [];

$admin['holidays']['parent_key'] = 'login';
$admin['holidays']['template']   = 'internal';
$admin['holidays']['title']      = lang('holidays_title', 'structure');
$admin['holidays']['icon']       = 'holiday';
$admin['holidays']['href']       = 'holidays';
$admin['holidays']['sort']       = 220;

$admin['holidays_table']['parent_key'] = 'holidays';
$admin['holidays_table']['template']   = 'internal';
$admin['holidays_table']['title']      = lang('holidays_table_title', 'structure');
$admin['holidays_table']['href']       = 'holidays';
$admin['holidays_table']['sort']       = 100;

$admin['holidays_calendar']['parent_key'] = 'holidays';
$admin['holidays_calendar']['template']   = 'internal';
$admin['holidays_calendar']['title']      = lang('holidays_calendar_title', 'structure');
$admin['holidays_calendar']['href']       = 'holidays/calendar';
$admin['holidays_calendar']['sort']       = 200;

return $admin;
