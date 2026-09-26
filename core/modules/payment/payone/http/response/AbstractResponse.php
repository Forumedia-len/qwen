<?php

namespace AC\core\modules\payment\payone\http\response;

use AC\core\modules\payment\payone\http\request\AbstractRequest;

abstract class AbstractResponse
{
  protected $data;
  protected $request;

  public function __construct(AbstractRequest $request, array $data)
  {
    $this->request = $request;
    $this->data    = $data;
  }
}