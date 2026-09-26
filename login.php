<?php


use AC\core\engines\ClientsEngine;
use AC\core\engines\Engines;

$r = new Engines();

$action   = Service::request()->validated('action', 'request', 'string');
$name     = Service::request()->validated('name', 'post', 'string');
$surname  = Service::request()->validated('surname', 'post', 'string');
$email    = Service::request()->validated('email', 'post', 'string');
$type_id  = Service::request()->validated('type_id', 'request', [['integer', ['min' => 0]]]);
$username = Service::request()->validated('username', 'post', 'string');
$password = Service::request()->validated('password', 'post', 'string');
$date     = Service::request()->validated('date', 'request', [['date', ['allowDottedFormat' => true, 'normalize' => true]]]);
$location = 'location:index.php';
if ($action == 'logIn') {
  if ($type_id !== null) {
    $type_id  = (int)$type_id;
    //указан тип и возможно дата - сразу на страницу резервирования
    $location = 'location:reservations.php?' . http_build_query(['type_id' => $type_id, 'date' => $date, 'page' => 1]);
  }
  $check_tab = Service::request()->validated('check_tab', 'request', [['integer', ['min' => 0, 'max' => 1]]]);
  if ($check_tab == 1 && GUEST && $name !== null && !$surname !== null && $email !== null) {
    //bar
    $client = new ClientsEngine();
    if (!$client->loginBar($name, $surname, $email, $error_code)) {
      $_SESSION['error'] = $error_code;
      $location          = 'location:index.php?loginError=true&loginErrorType=1&tab=1';
    }
  } else {
    if ($username !== null && $password !== null) {
      $r->clients->logOut();
      if (!$r->clients->login($username, $password, $error_code)) {
        //ошибка логина
        $location = 'location:index.php?loginError=true&loginErrorType=0&tab=0';
      }
    }
  }
} elseif ($action == 'logOut') {
  $r->clients->logOut();
}

header($location);
die;
