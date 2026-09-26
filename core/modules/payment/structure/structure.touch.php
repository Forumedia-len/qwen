<?php

$touch['payment_paypal'] = [
  'parent_key' => 'index',
  'template'   => 'default',
  'title'      => lang('payment_paypal_title', 'structure'),
];

$touch['payment_payone'] = [
  'parent_key' => 'index',
  'template'   => 'default',
  'title'      => lang('payment_payone_title', 'structure'),
  'href'       => 'payment/payone',
];

return $touch;