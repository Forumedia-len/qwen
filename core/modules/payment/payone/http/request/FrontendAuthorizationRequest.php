<?php

namespace AC\core\modules\payment\payone\http\request;

use AC\core\modules\payment\payone\http\request\method\UrlRequestMethod;

class FrontendAuthorizationRequest extends ServerAuthorizationRequest
{
  protected function setEndpoint($endpoint = null)
  {
    $this->endpoint = config('payment')->useFullProcessing() ? self::PAYONE_FRONTEND_URL : site_url('pay/mock.php');
  }

  protected function getPortalData()
  {
    return [
      'aid'         => $this->getParam('aid'),
      'portalid'    => $this->getParam('portalid'),
      'api_version' => $this->getParam('api_version'),
      'mode'        => $this->getParam('mode'),
      'request'     => $this->request_code,
      'encoding'    => $this->getParam('encoding'),
      'reference'   => $this->getParam('reference'),
      'param'       => $this->getParam('param'),
    ];
  }

  protected function createMethodRequest()
  {
    return new UrlRequestMethod();
  }
}