<?php

$admin = [];

//страница логина
$admin['login']['parent_key'] = false;
$admin['login']['template']   = 'auth';
$admin['login']['href']       = 'users/auth/logIn';
$admin['login']['title']      = lang('login_title', 'structure');

$admin['login_2FA']['parent_key'] = 'login';
$admin['login_2FA']['visible']    = false;
$admin['login_2FA']['template']   = 'auth';
$admin['login_2FA']['href']       = 'users/auth/verifyCode';
$admin['login_2FA']['title']      = lang('verify code', 'users');

//выход
$admin['logout']['parent_key'] = 'login';
$admin['logout']['template']   = 'internal';
$admin['logout']['title']      = lang('logout_title', 'structure');
$admin['logout']['icon']       = 'out';
$admin['logout']['href']       = 'users/auth/logOut';
$admin['logout']['access']     = 3;
$admin['logout']['sort']       = 400;

//главная часть - резервирования
$admin['reservations']['parent_key'] = 'login';
$admin['reservations']['template']   = 'internal';
$admin['reservations']['default']    = true;
$admin['reservations']['title']      = lang('reservations_title', 'structure');
$admin['reservations']['href']       = 'reservations.php';
$admin['reservations']['icon']       = 'home';
$admin['reservations']['access']     = 2;
$admin['reservations']['sort']       = 100;

//статистика
$admin['reservations_stats']['parent_key'] = 'reservations';
$admin['reservations_stats']['template']   = 'internal';
$admin['reservations_stats']['title']      = lang('reservations_stats_title', 'structure');
$admin['reservations_stats']['href']       = 'reservations.php';
$admin['reservations_stats']['sort']       = 100;
$admin['reservations_stats']['access']     = 2;

$admin['reservations_areas']['parent_key'] = 'reservations';
$admin['reservations_areas']['template']   = 'internal';
$admin['reservations_areas']['title']      = '';
$admin['reservations_areas']['href']       = 'reservations.php?action=showReservations&type_id=1&sport_id=1&date=' . date('Y-m-d');
$admin['reservations_areas']['sort']       = 200;
$admin['reservations_areas']['access']     = 2;

if (defined("SHOW_ADMIN_BAR") && SHOW_ADMIN_BAR) {
  $admin['reservations_bar']['parent_key'] = 'reservations';
  $admin['reservations_bar']['template']   = 'internal';
  $admin['reservations_bar']['title']      = lang('reservations_bar_title', 'structure');
  $admin['reservations_bar']['href']       = 'reservations.php?action=showBar&date=' . date('Y-m-d');
  $admin['reservations_bar']['sort']       = 300;
  $admin['reservations_bar']['access']     = 3;
}

//Купоны
if (GUTHABEN_COUPONS) {
  $admin['coupon']['parent_key'] = 'login';
  $admin['coupon']['template']   = 'internal';
  $admin['coupon']['icon']       = 'coupon';
  $admin['coupon']['title']      = lang('coupon_title', 'structure');
  $admin['coupon']['href']       = 'coupon.php';
  $admin['coupon']['sort']       = 160;
}

//блокировки
$admin['blocks']['parent_key'] = 'login';
$admin['blocks']['template']   = 'internal';
$admin['blocks']['icon']       = 'lock';
$admin['blocks']['title']      = lang('blocks_title', 'structure');
$admin['blocks']['href']       = 'blocks.php';
$admin['blocks']['sort']       = 240;

//блокировки
$admin['blocks_']['parent_key'] = 'blocks';
$admin['blocks_']['template']   = 'internal';
$admin['blocks_']['title']      = lang('blocks__title', 'structure');
$admin['blocks_']['href']       = 'blocks.php';
$admin['blocks_']['sort']       = 100;

//блокировки
$admin['blocks_periodical']['parent_key'] = 'blocks';
$admin['blocks_periodical']['template']   = 'internal';
$admin['blocks_periodical']['title']      = lang('blocks_periodical_title', 'structure');
$admin['blocks_periodical']['href']       = 'blocks.php?mode=periodical';
$admin['blocks_periodical']['sort']       = 200;


//блокировки
$admin['blocks_unlimited']['parent_key'] = 'blocks';
$admin['blocks_unlimited']['template']   = 'internal';
$admin['blocks_unlimited']['title']      = lang('blocks_unlimited_title', 'structure');
$admin['blocks_unlimited']['href']       = 'blocks.php?mode=unlimited';
$admin['blocks_unlimited']['sort']       = 300;


//площадки
$admin['areas']['parent_key'] = 'login';
$admin['areas']['template']   = 'internal';
$admin['areas']['title']      = lang('areas_title', 'structure');
$admin['areas']['icon']       = 'planning';
$admin['areas']['href']       = 'areas.php';
$admin['areas']['sort']       = 260;

// настройки регестрации
$admin['users']['parent_key'] = 'login';
$admin['users']['template']   = 'internal';
$admin['users']['title']      = lang('users_title', 'structure');
$admin['users']['icon']       = 'admin';
$admin['users']['href']       = 'users.php';
$admin['users']['divider']    = true;
$admin['users']['sort']       = 300;

//Свет
if (module('webIo')->useModel()->checkUse()) {
  $admin['light_monitor']['parent_key'] = 'login';
  $admin['light_monitor']['template']   = 'internal';
  $admin['light_monitor']['icon']       = 'webio';
  $admin['light_monitor']['title']      = lang('light_monitor_title', 'structure');
  $admin['light_monitor']['divider']    = true;
  $admin['light_monitor']['href']       = 'light_monitor.php';
  $admin['light_monitor']['sort']       = 320;
  
  $admin['webIo_monitor']['parent_key'] = 'light_monitor';
  $admin['webIo_monitor']['template']   = 'internal';
  $admin['webIo_monitor']['title']      = lang('webIo_monitor_title', 'structure');
  $admin['webIo_monitor']['href']       = 'light_monitor.php';
  $admin['webIo_monitor']['sort']       = 100;

//  $admin['door_monitor']['parent_key'] = 'light_monitor';
//  $admin['door_monitor']['template'] = 'internal';
//  $admin['door_monitor']['title'] = lang('door_monitor_title', 'structure');
//  $admin['door_monitor']['href'] = 'config_door.php';
  
  $admin['light_logs']['parent_key'] = 'light_monitor';
  $admin['light_logs']['template']   = 'internal';
  $admin['light_logs']['title']      = lang('light_logs_title', 'structure');
  $admin['light_logs']['href']       = 'light_logs.php';
  $admin['light_logs']['divider']    = true;
  $admin['light_logs']['sort']       = 200;
}

////новости
//$admin['news']['parent_key'] = 'login';
//$admin['news']['template']   = 'internal';
//$admin['news']['icon']       = 'news';
//$admin['news']['title']      = lang('news_title', 'structure');
//$admin['news']['href']       = 'news.php';
//$admin['news']['sort']       = 340;

//настройки
//баннеры
$admin['banners']['parent_key'] = 'login';
$admin['banners']['template']   = 'internal';
$admin['banners']['title']      = lang('banners_title', 'structure');
$admin['banners']['icon']       = 'banner';
$admin['banners']['href']       = 'banners.php';
$admin['banners']['sort']       = 380;

return $admin;