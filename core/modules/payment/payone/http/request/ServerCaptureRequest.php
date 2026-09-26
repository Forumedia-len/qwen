<?php

namespace AC\core\modules\payment\payone\http\request;

class ServerCaptureRequest extends ServerAuthorizationRequest
{
  protected $request_code = 'capture';

  protected function setEndpoint($endpoint = null)
  {
    $this->endpoint = self::PAYONE_SERVER_URL;
  }

  protected function getPortalData()
  {
    return [
      'mid'         => $this->getParam('mid'),
//      'aid'         => $this->getParam('aid'),
      'portalid'    => $this->getParam('portalid'),
      'key'         => md5($this->getParam('key')),
      'api_version' => $this->getParam('api_version'),
      'mode'        => $this->getParam('mode'),
      'request'     => $this->request_code,
      'encoding'    => $this->getParam('encoding'),
      'reference'   => $this->getParam('reference'),
      'param'       => $this->getParam('param'),
    ];
  }
  public function getData()
  {
    return array_merge(
      $this->getPortalData(),
//      $this->getParamsType('personal'),
//      $this->getParamsType('order'),
      [
        'amount' => $this->getParam('amount', 'order'),
//        'amount' => '0.01',
        'currency' => $this->getParam('currency', 'order'),
        'txid' => $this->getParam('txid'),
        'sequencenumber' => $this->getParam('sequencenumber') + 1,
//        'sequencenumber' => 1,
        "capturemode" => "completed",
      ]
    );

  }
}