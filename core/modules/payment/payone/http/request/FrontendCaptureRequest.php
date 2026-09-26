<?php

namespace AC\core\modules\payment\payone\http\request;

class FrontendCaptureRequest extends ServerCaptureRequest
{
  protected function setEndpoint($endpoint = null)
  {
    $this->endpoint = !LOCAL_SERVER ? self::PAYONE_SERVER_URL : site_url('pay/mock.php');
  }
}