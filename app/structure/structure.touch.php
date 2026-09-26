<?php
// 
use AC\core\engines\Engines;

$touch['index']['parent_key'] = false;
$touch['index']['template']   = 'main';
$touch['index']['title']      = 'index';
$touch['index']['href']       = 'index.php';

$touch['login']['parent_key'] = 'index';
$touch['login']['template']   = 'default';
$touch['login']['title']      = 'index';
$touch['login']['href']       = 'login.php';

$touch['reservations']['parent_key'] = 'index';
$touch['reservations']['template'] = 'reservation';
$touch['reservations']['title'] = 'reservations';
$touch['reservations']['href'] = 'reservations.php';
$type = 0;
foreach ((new Engines())->areas->selectActiveType('type_id') as $type => $item) {
$touch['reservations_'.$type]['parent_key'] = 'index';
$touch['reservations_'.$type]['template'] = 'reservation';
$touch['reservations_'.$type]['title'] = $item->title;
$touch['reservations_'.$type]['href'] = 'reservations.php?type_id=' . $type . '&page=1';
$touch['reservations_'.$type]['id'] = '$item->alias';


$touch['login_'.$type]['parent_key'] = 'index';
$touch['login_'.$type]['template'] = 'reservation';
$touch['login_'.$type]['title'] = $item->title;
$touch['login_'.$type]['href'] = 'login.php?type_id=' . $type;
$touch['login_'.$type]['id'] = $item->alias;
}

foreach (getDataOfCombinedSites('touch', 'login') as $key => $combinedSite) {
  $type++;
  $touch[$key] = $combinedSite;
}

$touch['registration']['parent_key'] = 'index';
$touch['registration']['template'] = 'default';
$touch['registration']['title'] = lang('registration_title', 'structure');
$touch['registration']['href'] = 'registration.php';

$touch['join_login']['parent_key'] = 'index';
$touch['join_login']['template'] = 'default';
$touch['join_login']['title'] = 'reservations';
$touch['join_login']['href'] = 'join_login.php';

$touch['error']['parent_key'] = 'index';
$touch['error']['template'] = 'default';
$touch['error']['title'] = lang('error_title', 'structure');
$touch['error']['title_image'] = 'section_img_aktuelles';
$touch['error']['logo'] = 'не используется';

$touch['payment_paypal'] = [
  'parent_key' => 'index',
  'template' => 'default',
  'title' => lang('payment_paypal_title', 'structure'),
];

return $touch;