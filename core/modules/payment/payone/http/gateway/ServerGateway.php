<?php

namespace AC\core\modules\payment\payone\http\gateway;

class ServerGateway extends AbstractGateway
{
  protected function createRequest($request, $parameters)
  {
    $className = $this->generateClassNameRequest('server', $request);

    return useClass($className, true, $parameters);
  }
}