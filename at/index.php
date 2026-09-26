<?php

$_page['key'] = 'login';

$auth = Service::auth();
//совершается какое-то действие
if (isset($_GET['action']) && $_GET['action'] == 'logOut') {
  //лог аут
  $auth->logOut();
} elseif (isset($_GET['action']) && $_GET['action'] == 'logIn') {
  //лог ин
  if ($auth->logIn($_POST['username'], $_POST['password']) == true) {
    $structure = Service::structure();
    Service::redirect()->redirect(site_url($structure->GetPageHrefByKey('reservations')))->send();
    die ();
  } else {
    $_page['content'][0] = '<span style="color:red;">'.lang('Error! Username or Password incorrect!', 'message_error').'</span>';
  }
} else {
  //нет действий
  if ($auth->checkAuth() == true) {
    //чел уже залогинен, путь идет в первую страницу админа
    $structure = Service::structure();
    Service::redirect()->redirect(site_url($structure->GetPageHrefByKey('reservations')))->send();
    die ();
  }
}