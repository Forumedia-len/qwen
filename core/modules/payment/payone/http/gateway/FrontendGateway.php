<?php

namespace AC\core\modules\payment\payone\http\gateway;

class FrontendGateway extends ServerGateway
{
  protected function createRequest($request, $parameters)
  {
    $className = $this->generateClassNameRequest('frontend', $request);

    return useClass($className, true, $parameters);
  }
}