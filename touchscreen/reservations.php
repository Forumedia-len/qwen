<?php

$_page = Service::app()->execModule('reservations');

$_page['js'][]  = ['select2.min', 'third/select2', false, 'cdn'];
$_page['css'][] = ['select2.min', 'third/select2', false, 'cdn'];
$_page['js'][]  = ['all_device', 'common', false, 'cdn'];
$_page['js'][]  = ['popup_box', 'common', false, 'cdn'];
$_page['css'][] = ['popup_box', 'common', false, 'cdn'];

$type_id        = (int)Service::request()->_('type_id');
$reservations   = config('reservations');
$isOpenType     = $reservations->isOpenType($type_id);
$isMcArenaType  = $reservations->isMcArenaType($type_id);

$action = Service::request()->_('action');
$reload = false;
switch ($action) {
  case 'showOrder':
    $template_page = 'order';
    break;
  case 'proceedOrder':
    $template_page = !$isOpenType ? 'default' : 'order';
    $show          = 'ok';
    $reload        = !$isOpenType;
    break;
  case 'removeOrder':
  case 'confirmOrder':
  case 'join':
  case 'unJoin':
    $template_page = 'default';
    $show          = 'ok';
    $reload        = true;
    break;
  default :
    $template_page = 'reservation';
    break;
}
$ajax       = Service::request()->_('ajax', 0);
$ajaxPayPal = Service::request()->_('ajaxPayPal', 0);
if (!$ajax) {
  if (!$reload || (!$isOpenType && !$isMcArenaType) || $ajaxPayPal) {
    $_page['template'] = $template_page;
  } else {
    header(
      'Location:' . site_url() . 'reservations.php?action=showReservation&type_id=' . $type_id . '&area_id=' . Service::request()->_(
        'area_id',
        1
      ) . '&sport_id=' . (Service::request()->_('sport_id', OPEN_DEFAULT_SPORT_ID)) . '&page=' . Service::request()->_('page',
        1) . '&date=' . Service::request()->_(
        'date',
        date(
          'Y-m-d'
        )
      )
    );
  }
} else {
  if (Service::app()->getModule()->getController()->view->checkError && $isOpenType) {
    $_page['content'][1] = '<div class="alignC"><br /><br /><br /><br /><br /><strong class="f28 red">' . $_page['content'][1] . '</strong><br /><br /><br /></div>';
  }
  echo $_page['content'][1];
}
