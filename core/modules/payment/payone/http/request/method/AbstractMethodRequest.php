<?php

namespace AC\core\modules\payment\payone\http\request\method;

use AC\core\modules\payment\payone\http\request\AbstractRequest;

abstract class AbstractMethodRequest
{
  abstract public function sendRequest(AbstractRequest $request, $data);
}