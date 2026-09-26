<?php

namespace AC\core\modules\payment\payone\http\request;

use AC\core\modules\payment\payone\http\request\method\CurlRequestMethod;

class ServerAuthorizationRequest extends AbstractRequest
{
  protected $request_code = 'authorization';

  protected function getPortalData()
  {
    return [
      'mid'         => $this->getParam('mid'),
      'aid'         => $this->getParam('aid'),
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
      $this->getParamsType('personal'),
      $this->getParamsType('order'),
      $this->getParamsType('url')
    );
  }

  protected function setEndpoint($endpoint = null)
  {
    $this->endpoint = self::PAYONE_SERVER_URL;
  }

  protected function createMethodRequest()
  {
    return new CurlRequestMethod();
  }
}