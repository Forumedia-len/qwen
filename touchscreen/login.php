<?php

/**
 *  todo доработать выбор спорта на данной площадке это не используется
 * @var  Engines $engine
 * @var  int     $type_id
 * @var  Page    $page
 * @var  string  $type_template open|close
 */


use AC\core\engines\ClientsEngine;
use AC\core\engines\Engines;
use AC\core\system\view\Page;

if (isset ($_SESSION['reservation_comment'])) {
  unset($_SESSION['reservation_comment']);
} elseif ($engine->clients->checkAuthorization()) {
  $engine->clients->logOut();
}
$login_type = Service::request()->_('type', 'login');

$reservation_id = Service::request()->_('reservation_id', false);
$area_id        = Service::request()->_('area_id', false);
$date           = Service::request()->_('date', false);
$time           = Service::request()->_('time', false);

switch ($login_type) {
  case 'card':
    $codeCart = Service::request()->_('codecard');
    if ($engine->clients->loginCard($codeCart)) {
      if ($reservation_id) {
        header(
          'location:reservations.php?action=join&reservation_id=' . $reservation_id . '&area_id=' . $area_id . '&date=' . $date . '&time=' . $time
        );
      } else {
        header(
          'location:reservations.php?action=selectSport&type_id=' . $type_id
        );
      }
    } else {
      $error_str = lang('error_code_invalid', 'login');
    }
    break;
  case 'login':
    $login    = Service::request()->_('login');
    $password = Service::request()->_('password');
    if ($login && $password) {
      if ($engine->clients->login($login, $password, $error_code)) {
        if ($reservation_id) {
          $_SESSION['right'] = '1';
        }
        header('location:reservations.php?action=selectSport&type_id=' . $type_id);
      } else {
        $error_str = lang('error_username_or_password_invalid', 'login');
      }
    }
    break;
  case 'guest':
    //bar
    $name    = Service::request()->_('name');
    $surname = Service::request()->_('surname');
    $email   = Service::request()->_('email');
    $client  = new ClientsEngine();
    if ($client->loginBar($name, $surname, $email, $error_code)) {
      header('location:reservations.php?action=selectSport&type_id=' . $type_id);
    } else {
      $_SESSION['error'] = $error_code;
    }
    break;
  case 'guest_bar':
    //bar
    $name    = Service::request()->_('name');
    $surname = Service::request()->_('surname');
    $email   = Service::request()->_('email');
    $client  = new ClientsEngine();
    if ($client->loginBar($name, $surname, $email, $error_code, false)) {
      header('location:reservations.php?action=selectSport&type_id=' . $type_id);
    } else {
      $_SESSION['error'] = $error_code;
    }
    break;
}


$_page['key']                        = 'login';
$_page['mainScheduleBlock']['style'] = 'background: none';
$page->setByKey('tpl_view', 'login');
$_page['content'][1] = $page->render(
  $type_template,
  array_merge(
  array(
    'login_type'     => $login_type,
    'reservation_id' => $reservation_id,
    'area_id'        => $area_id,
    'date'           => $date,
    'time'           => $time,
    'error_str'      => $error_str ?? null
  ), get_defined_vars())
);
$page->_back_href    = 'touchscreen/';