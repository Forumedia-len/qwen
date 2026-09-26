<?php

$user['payment_paypal'] = [
  'parent_key' => 'index',
  'template'   => 'default',
  'title'      => lang('payment_paypal_title', 'structure'),
  'href'       => 'payment/payone',
];

$user['payment_payone'] = [
  'parent_key' => 'index',
  'template'   => 'default',
  'title'      => lang('payment_payone_title', 'structure'),
  'href'       => 'payment/payone',
];
return $user;