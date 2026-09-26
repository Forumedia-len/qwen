<?php

namespace AC\core\modules\payment\payone\http\gateway;

use AC\core\modules\payment\payone\http\request\AbstractRequest;
use AC\core\modules\payment\payone\http\response\AbstractResponse;

abstract class AbstractGateway
{
  private string $modulePath;

  /**
   * @param                 $request
   * @param                 $parameters
   *
   * @return AbstractRequest
   */
  abstract protected function createRequest($request, $parameters);

  /**
   * @param $request
   * @param $data
   *
   * @return AbstractResponse
   */
  public function sendRequest($request, $data)
  {
    return $this->createRequest($request, $data)->send();
  }

  public function __construct($modulePath)
  {
    $this->modulePath = $modulePath;
  }

  public function getModulePath(): string
  {
    return $this->modulePath;
  }

  protected function generateClassNameRequest(string $requestType, string $request): string
  {
    return $this->getModulePath() . ucfirst($requestType) . ucfirst($request) . 'Request';
  }
}