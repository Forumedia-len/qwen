<?php

namespace AC\core\modules\payment\payone\actions\orders;

use AC\core\modules\payment\payone\helpers\PayoneErrorHelper;

trait ErrorsTrait
{
  protected string $currentError;
  protected string $errorCode;

  public function setError($code): void
  {
    if (!$this->checkError()) {
      $this->errorCode    = $code;
      $this->currentError = PayoneErrorHelper::checkCode($code) ? PayoneErrorHelper::getError($code) : $code;
    }
  }

  protected function checkError(): bool
  {
    return !empty($this->currentError);
  }
}