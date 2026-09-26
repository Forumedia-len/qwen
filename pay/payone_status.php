<?php

use AC\core\modules\payment\services\OnlineGatewayService;

//uses('pay.config');
//Debug()::sv($_POST);

$param = [];
if (!empty($_POST['param'])) {
  $param = explode('|', (string)$_POST['param']);
}
$config = OnlineGatewayService::payoneConfig(['typeKey' => rawurldecode($param[1] ?? '')]);

if ($config->verifyTransactionStatusKey((string)($_POST['key'] ?? ''), (string)($_POST['portalid'] ?? ''))) {
  echo "TSOK";
  ob_flush();
  flush();
  if (in_array($param[0] ?? '', ['loc', 'test'], true)) {
    $url_test       = $param[0] == 'loc' ? BASE_HREF . 'pay/payone_status.php' : 'https://testsystem.active-court.com/pay/payone_status.php';
    $param[0]       = '';
    $_POST['param'] = implode('|', $param);

    sendRequest($url_test, $_POST);
  } else {
    module('payment', ['subName' => 'payone'])->exec('status');
  }
}


function sendRequest($url, $data)
{
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8']);

  $response = curl_exec($ch);

  if (!$response && curl_errno($ch)) {
    $response['error'] = ['number' => curl_errno($ch), 'message' => curl_error($ch)];
  }

  curl_close($ch);

  return $response;
}