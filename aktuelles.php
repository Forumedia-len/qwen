<?php

$_page = Service::app()->execModule('news');

//проверка на ошибку логина

if (Service::request()->checkGet('loginError')) {
  $_page['login_error'] = true;
  if (Service::request()->checkGet('loginErrorType') && is_numeric(Service::request()->_get('loginErrorType'))) {
    $_page['login_error_type'] = Service::request()->_get('loginErrorType');
  } else {
    $_page['login_error_type'] = 0;
  }
}


$_page['content'][1] = Service::view()->renderer(paths()->getTplDir('_banners.php')) . $_page['content'][1];