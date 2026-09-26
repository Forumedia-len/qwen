<?php

namespace AC\core\modules\payment\payone\http\request;

class ServerPreauthorizationRequest extends ServerAuthorizationRequest
{
  protected $request_code = 'preauthorization';

  protected function setEndpoint($endpoint = null)
  {
    $this->endpoint = self::PAYONE_SERVER_URL;
  }

}