<?php

namespace AC\pay;

use AC\core\modules\payment\services\OnlineGatewayService;
use Service;

class mock
{
  public function start()
  {
    match (Service::request()->_('request')) {
      'authorization' => $this->authorization(),
      'capture'       => $this->capture(),
      default         => ''
    };
  }

  private function authorization()
  {
    $this->sendRequest(null, $this->getData($_GET));
    $this->sendRequest(null, $this->getData($_GET, 'paid'));
//    $this->sendRequest(null, $this->getData($_GET, 'failed'));
    header('Location: ' . Service::request()->_("successurl"));
  }

  private function sendRequest($url, $data)
  {
    $url = $url ?? site_url('pay/payone_status.php');
    $ch  = curl_init();

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

  private function capture()
  {
  }

  private function getData($getData = [], $txaction = 'appointed')
  {
    $payoneConfig = OnlineGatewayService::payoneConfig();
    return [
      'key'            => md5($payoneConfig->key),
      'txaction'       => $txaction,
      'portalid'       => $payoneConfig->portalid,
      'aid'            => $payoneConfig->aid,
      'clearingtype'   => $getData['clearingtype'],
      'wallettype'     => $getData['wallettype'] ?? null,
      'notify_version' => '7.4',
      'param'          => $getData['param'],
      'txid'           => '1456563111',
      'mode'           => $getData['mode'],
      'price'          => round($getData['amount'] / 100, 2),
      'reference'      => $getData['reference'],
      'sequencenumber' => 0,
      'company'        => null,
      'firstname'      => $getData['firstname'],
      'lastname'       => $getData['lastname'],
      'street'         => $getData['street'],
      'zip'            => $getData['zip'],
      'city'           => $getData['city'],
      'email'          => $getData['email'],
      'country'        => $getData['country'],
      'customerid'     => $getData['customerid'],
      'currency'       => $getData['currency'],
      'id'             => $getData['id'],
      'pr'             => $getData['pr'],
      'no'             => $getData['no'],
      'de'             => $getData['de'],
      'it'             => $getData['it'],
      'va'             => $getData['va'],
    ];
  }
}

(new mock())->start();


