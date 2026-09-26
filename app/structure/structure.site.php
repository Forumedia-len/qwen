<?php

//страница логина
$user['index']['parent_key'] = false;


//внешние страницы
$user['homepage']['href']   = config('app')->getHomePage();
$user['homepage']['title']  = lang('home_title', 'structure');
if(config('app')->getHomePage()) {
  $user['homepage']['target'] = '_blank';
}
$user['homepage']['id']     = 'homepage';
$user['homepage']['menu']  = true;
$user['homepage']['sort']  = 100;

/*
// пример вывода в меню страницы инфо
$user['homepage']['menu'] = false;

$user['text_info']['parent_key'] = 'index';
$user['text_info']['href']       = 'text/show/alias/info';
$user['text_info']['title']      = 'Info';
$user['text_info']['target']     = null;
$user['text_info']['id']         = 'text_info';
$user['text_info']['menu']  = true;
$user['text_info']['sort']  = 101;

*/


$type_i = 0;
foreach (Service::engines()->areas->selectActiveType('type_id') as $type => $item) {
  $type_i++;
  if (defined('HIDE_GUEST_MENU') && HIDE_GUEST_MENU == $item->alias && Service::engines()->clients->isBar()) {
    if ((int)Service::request()->_get('type_id') == $type) {
      $id = Service::engines()->areas->selectActiveType('alias')[$item->alias == 'close' ? 'open' : 'close']->type_id;
      header('HTTP/1.1 301 Moved Permanently');
      header('Location:' . BASE_HREF . 'reservations.php?type_id=' . $id . '&page=1');
    }
  } else {
    $user['reservations_' . $type]['parent_key']      = 'index';
    $user['reservations_' . $type]['title']           = $item->title;
    $user['reservations_' . $type]['title_image']     = 'section_img_tennis';
    $user['reservations_' . $type]['logo']            = null;
    $user['reservations_' . $type]['href']            = 'reservations.php?type_id=' . $type . '&page=1' . (DEFAULT_TAGESANSICHT_WOCHENANSICHT == 'WOCHENANSICHT'?'&week=1':'');
    $user['reservations_' . $type]['show_date']       = true;
    $user['reservations_' . $type]['id']              = $item->alias;
    $user['reservations_' . $type]['mobile_calendar'] = true;
    $user['reservations_' . $type]['menu']  = true;
    $user['reservations_' . $type]['sort']  = 200 + $type_i * 10;
  }
}

foreach (getDataOfCombinedSites() as $key => $combinedSite) {
  $type_i++;
  $user[$key] = $combinedSite;
  $user[$key]['menu']  = true;
  $user[$key]['sort']  = 200 + $type_i * 10;
}
/** @todo нужно разобраться эту опцию переносим на локальный структурный файл и вручную выбираем тип*/
//if(USE_THIRD_PARTY_LINKS_IN_DROP_DOWN_MENU ) {
//  for ($i = 0; $i < USE_THIRD_PARTY_LINKS_IN_DROP_DOWN_MENU; $i++) {
//    $type_i++;
//    $user['reservations_' . $type]['parent_key']  = 'index';
//    $user['reservations_' . $type]['template']    = 'tpl_reservations';
//    $user['reservations_' . $type]['title']       = 'Abo-anfrage';
//    $user['reservations_' . $type]['href']        = 'https://abobuchung.tennis-pfungstadt.de/';
//    $user['reservations_' . $type]['target'] = '_blank';
//    $user['reservations_' . $type]['menu']  = true;
//    $user['reservations_' . $type]['sort']  = 200 + $type * 10;
//
//  }
//}

//aktuelles
$user['aktuelles']['parent_key']  = 'index';
$user['aktuelles']['default']     = true;
$user['aktuelles']['title']       = lang('news_title', 'structure');
$user['aktuelles']['title_image'] = 'section_img_aktuelles';
$user['aktuelles']['logo']        = null;
$user['aktuelles']['href']        = 'aktuelles.php';
$user['aktuelles']['id']          = 'aktuelles';
$user['aktuelles']['menu']  = true;
$user['aktuelles']['sort']  = 300;

//price
$user['price']['parent_key']  = 'index';
$user['price']['title']       = lang('price_title', 'structure');
$user['price']['title_image'] = 'section_img_aktuelles';
$user['price']['logo']        = null;
$user['price']['href']        = 'price.php';
$user['price']['id']          = 'price';
$user['price']['menu']  = true;
$user['price']['sort']  = 400;

//impressum
$user['impressum']['parent_key']  = 'index';
$user['impressum']['title']       = lang('contact_title', 'structure');
$user['impressum']['title_image'] = 'section_img_aktuelles';
$user['impressum']['logo']        = null;
$user['impressum']['href']        = 'impressum.php';
$user['impressum']['id']          = 'impressum';
$user['impressum']['menu']  = true;
$user['impressum']['sort']  = 500;


//registration
$user['registration']['parent_key']  = 'index';
$user['registration']['title']       = lang('registration_title', 'structure');
$user['registration']['title_image'] = 'section_img_aktuelles';
$user['registration']['logo']        = null;
$user['registration']['href']        = 'registration.php';
$user['registration']['js'][]        = ['maskedinput', 'common', false, 'cdn'];

//recover_password
$user['recover_password']['parent_key']  = 'index';
$user['recover_password']['title']       = lang('recover_password_title', 'structure');
$user['recover_password']['title_image'] = 'section_img_aktuelles';
$user['recover_password']['logo']        = null;
$user['recover_password']['href']        = 'recover_password.php';

if (config('payment')->showPrivateAccountPaymentMethod()) {
  $user['prepayment']['parent_key']  = 'user_auth';
  $user['prepayment']['title_image'] = 'section_img_aktuelles';
  $user['prepayment']['logo']        = null;
  $user['prepayment']['title']       = lang('balance_title', 'structure');
  $user['prepayment']['title_mess']  = 'my_guthaben';
  $user['prepayment']['href']        = 'prepayment.php';
}

$user['data_user']['parent_key']  = 'user_auth';
$user['data_user']['title_image'] = 'section_img_aktuelles';
$user['data_user']['title_mess']  = 'my_data';
$user['data_user']['logo']        = null;
$user['data_user']['href']        = 'registration.php?action=view';

//Clients_reservation
$user['clients_reservations']['parent_key']  = 'user_auth';
$user['clients_reservations']['title_image'] = 'section_img_aktuelles';
$user['clients_reservations']['logo']        = null;
$user['clients_reservations']['title']       = lang('clients_reservations_title', 'structure');
$user['clients_reservations']['title_mess']  = 'my_booking';
$user['clients_reservations']['href']        = 'clients_reservations.php';

$user['logout']['parent_key']  = 'user_auth';
$user['logout']['bar']         = true;
$user['logout']['title_image'] = 'section_img_aktuelles';
$user['logout']['logo']        = null;
$user['logout']['title']       = lang('logout_title', 'structure');
$user['logout']['title_mess']  = 'logout';
$user['logout']['href']        = 'login.php?action=logOut';

//error
$user['error']['parent_key']  = 'index';
$user['error']['title']       = lang('error_title', 'structure');
$user['error']['title_image'] = 'section_img_aktuelles';
$user['error']['logo']        = null;

return $user;