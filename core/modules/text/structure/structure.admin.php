<?php

$admin = [];

$admin['config_text']['parent_key'] = 'login';
$admin['config_text']['template']   = 'internal';
$admin['config_text']['title']      = lang('config_text_title', 'structure');
$admin['config_text']['icon']       = 'text';
$admin['config_text']['href']       = 'text/alias/address';
$admin['config_text']['sort']       = 360;

$admin['config_text_address_home']['parent_key'] = 'config_text';
$admin['config_text_address_home']['template']   = 'internal';
$admin['config_text_address_home']['title']      = lang('config_text_address_home_title', 'structure');
$admin['config_text_address_home']['href']       = 'text/alias/address_home';
$admin['config_text_address_home']['sort']       = 100;

$admin['config_text_info']['parent_key'] = 'config_text';
$admin['config_text_info']['template']   = 'internal';
$admin['config_text_info']['title']      = lang('config_text_info_title', 'structure');
$admin['config_text_info']['href']       = 'text/alias/info';
$admin['config_text_info']['sort']       = 200;

$admin['config_text_working_time']['parent_key'] = 'config_text';
$admin['config_text_working_time']['template']   = 'internal';
$admin['config_text_working_time']['title']      = lang('config_text_working_time', 'structure');
$admin['config_text_working_time']['href']       = 'text/alias/working_time';
$admin['config_text_working_time']['sort']       = 300;

$admin['config_text_address']['parent_key'] = 'config_text';
$admin['config_text_address']['template']   = 'internal';
$admin['config_text_address']['title']      = lang('config_text_address_title', 'structure');
$admin['config_text_address']['href']       = 'text/alias/address';
$admin['config_text_address']['sort']       = 400;

$admin['config_text_price']['parent_key'] = 'config_text';
$admin['config_text_price']['template']   = 'internal';
$admin['config_text_price']['title']      = lang('config_text_price_title', 'structure');
$admin['config_text_price']['href']       = 'text/alias/price';
$admin['config_text_price']['sort']       = 500;

$admin['config_text_prepayment']['parent_key'] = 'config_text';
$admin['config_text_prepayment']['template']   = 'internal';
$admin['config_text_prepayment']['title']      = lang('config_text_prepayment_title', 'structure');
$admin['config_text_prepayment']['href']       = 'text/alias/prepayment';
$admin['config_text_prepayment']['sort']       = 600;

if (MC_ARENA) {
  $admin['config_text_pay']['parent_key'] = 'config_text';
  $admin['config_text_pay']['template']   = 'internal';
  $admin['config_text_pay']['title']      = lang('config_text_pay_title', 'structure');
  $admin['config_text_pay']['href']       = 'text/alias/pay';
  $admin['config_text_pay']['sort']       = 700;
}

if (config('account')->useSEPA()) {
  $admin['config_text_sepa']['parent_key'] = 'config_text';
  $admin['config_text_sepa']['template']   = 'internal';
  $admin['config_text_sepa']['title']      = lang('config_text_sepa_title', 'structure');
  $admin['config_text_sepa']['href']       = 'text/alias/sepa';
  $admin['config_text_sepa']['sort']       = 800;
}

$admin['config_text_rights']['parent_key'] = 'config_text';
$admin['config_text_rights']['template']   = 'internal';
$admin['config_text_rights']['title']      = lang('config_text_rights_title', 'structure');
$admin['config_text_rights']['href']       = 'text/alias/rights';
$admin['config_text_rights']['sort']       = 900;

$admin['config_text_cookie']['parent_key'] = 'config_text';
$admin['config_text_cookie']['template']   = 'internal';
$admin['config_text_cookie']['title']      = lang('config_text_cookie_title', 'structure');
$admin['config_text_cookie']['href']       = 'text/alias/cookie';
$admin['config_text_cookie']['sort']       = 1000;

$admin['config_messages']['parent_key'] = 'config_text';
$admin['config_messages']['template']   = 'internal';
$admin['config_messages']['title']      = lang('config_messages_title', 'structure');
$admin['config_messages']['href']       = 'text/messages/device/site/courtType/common';
$admin['config_messages']['sort']       = 1100;

return $admin;

