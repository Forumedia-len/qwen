<?php

namespace AC\core\modules\payment\payone\http\request\method;

use AC\core\modules\payment\payone\http\request\AbstractRequest;

class UrlRequestMethod extends AbstractMethodRequest
{
  public function sendRequest(AbstractRequest $request, $data)
  {
    $url = $request->getEndpoint() . '?' . http_build_query($data);

    return header('Location:' . $url);
  }
}