<?php

namespace AC\core\modules\payment\payone\session;

use AC\core\system\session\Session;
use AC\core\system\session\SessionValueAsArray;

class PayoneSession extends Session
{
  use SessionValueAsArray;

  public function validKeys(): array
  {
    return ['PREP_PAY_DATA', 'PAY_DATA'];
  }

  public function setSessionKeyByPayment(string $payment): self
  {
    $sessionKey = match ($payment) {
      'AccountReplenishment' => 'PREP_PAY_DATA',
      'reservation'          => 'PAY_DATA',
      default                => null,
    };

    $this->setSessionKey($sessionKey);

    return $this;
  }

  public function getPaymentOrderPrefix(?string $sessionKey = null): ?string
  {
    $sessionKey ??= $this->sessionKey();
    return match ($sessionKey) {
      'PREP_PAY_DATA' => 'AccountReplenishment',
      'PAY_DATA'      => 'reservation',
      default         => null,
    };
  }

  public function getPaymentOrderPrefixForActionRequest($request): ?string
  {
    return match ($request) {
      'prepayment'  => 'AccountReplenishment',
      'reservation' => 'reservation',
      default       => null,
    };
  }
}