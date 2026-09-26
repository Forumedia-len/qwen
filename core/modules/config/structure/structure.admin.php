<?php

$admin = [];
//настройки
$admin['config_']['parent_key'] = 'login';
$admin['config_']['template']   = 'internal';
$admin['config_']['icon']       = 'settings';
$admin['config_']['title']      = lang('config__title', 'structure');
$admin['config_']['href']       = 'config.php?mode=count';
$admin['config_']['sort']       = 280;

//настройки обшие
$admin['config_count']['parent_key'] = 'config_';
$admin['config_count']['template']   = 'internal';
$admin['config_count']['title']      = lang('config_title', 'structure');
$admin['config_count']['href']       = 'config.php?mode=count';
$admin['config_count']['sort']       = 100;

if (defined('SEND_ADMIN_TO_DIFFERENT_EMAIL') && SEND_ADMIN_TO_DIFFERENT_EMAIL) {
// настройки раздельных email admin
  $admin['config_email']['parent_key'] = 'config_';
  $admin['config_email']['template']   = 'internal';
  $admin['config_email']['title']      = lang('config_email_title', 'structure');
  $admin['config_email']['href']       = 'config.php?mode=email';
  $admin['config_email']['sort']       = 150;
  $admin['config_']['j0']              = false;
}
//настройки НДС
$admin['config_nds']['parent_key'] = 'config_';
$admin['config_nds']['template']   = 'internal';
$admin['config_nds']['title']      = lang('config_nds_title', 'structure');
$admin['config_nds']['href']       = 'config.php?mode=nds';
$admin['config_nds']['sort']       = 200;

//настройки скидок
$admin['config_extra']['parent_key'] = 'config_';
$admin['config_extra']['template']   = 'internal';
$admin['config_extra']['title']      = lang('config_extra_title', 'structure');
$admin['config_extra']['href']       = 'config.php?mode=extra';
$admin['config_extra']['sort']       = 300;

//настройки Наценки
$admin['config_discount']['parent_key'] = 'config_';
$admin['config_discount']['template']   = 'internal';
$admin['config_discount']['title']      = lang('config_discount_title', 'structure');
$admin['config_discount']['href']       = 'config_discount.php';
$admin['config_discount']['sort']       = 400;

// Онлайн-оплата (ConfigOnlinePaymentControllerAdmin, mode=online_payment).
$admin['config_online_payment']['parent_key'] = 'config_';
$admin['config_online_payment']['template']   = 'internal';
$admin['config_online_payment']['title']      = lang('config_online_payment_title', 'structure');
$admin['config_online_payment']['href']       = 'config.php?mode=online_payment&tab=gateway';
$admin['config_online_payment']['sort']       = 650;

$admin['config_online_payment_tab_gateway']['parent_key']    = 'config_online_payment';
$admin['config_online_payment_tab_gateway']['template']      = 'internal';
$admin['config_online_payment_tab_gateway']['title']         = lang('tab_gateway', 'config_online_payment');
$admin['config_online_payment_tab_gateway']['href']          = 'config.php?mode=online_payment&tab=gateway';
$admin['config_online_payment_tab_gateway']['sort']          = 100;
$admin['config_online_payment_tab_gateway']['content_menu']  = true;

$admin['config_online_payment_tab_guthaben']['parent_key']   = 'config_online_payment';
$admin['config_online_payment_tab_guthaben']['template']     = 'internal';
$admin['config_online_payment_tab_guthaben']['title']        = lang('tab_guthaben', 'config_online_payment');
$admin['config_online_payment_tab_guthaben']['href']         = 'config.php?mode=online_payment&tab=guthaben';
$admin['config_online_payment_tab_guthaben']['sort']         = 200;
$admin['config_online_payment_tab_guthaben']['content_menu'] = true;

$admin['config_webIo']['parent_key'] = 'config_';
$admin['config_webIo']['template']   = 'internal';
$admin['config_webIo']['title']      = lang('config_webIo_title', 'structure');
$admin['config_webIo']['href']       = 'config.php?mode=webIo';
$admin['config_webIo']['sort']       = 700;

// настройки регестрации
$admin['config_registration']['parent_key'] = 'config_';
$admin['config_registration']['template']   = 'internal';
$admin['config_registration']['title']      = lang('config_registration_title', 'structure');
$admin['config_registration']['href']       = 'config.php?mode=registration';
$admin['config_registration']['sort']       = 900;

// настройки построения цены для открытых кортов и кто с кем может играть.
// видны только  adminvs
if (module('areas')->useModel()->checkTypeIsActiveByAlias('open')) {
  $admin['config_open_type']['parent_key'] = 'config_';
  $admin['config_open_type']['template']   = 'internal';
  $admin['config_open_type']['title']      = lang('config_open_type_title', 'structure');
  $admin['config_open_type']['href']       = 'config.php?mode=open_type';
  $admin['config_open_type']['sort']       = 1000;
  $admin['config_open_type']['access']     = 0;
}

$admin['config_reservation']['parent_key'] = 'config_';
$admin['config_reservation']['template']   = 'internal';
$admin['config_reservation']['title']      = lang('config_reservation_title', 'structure');
$admin['config_reservation']['href']       = 'config.php?mode=reservation';
$admin['config_reservation']['sort']       = 1100;
$admin['config_reservation']['access']     = 1;

return $admin;