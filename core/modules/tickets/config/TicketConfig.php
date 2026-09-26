<?php

namespace AC\core\modules\tickets\config;

use Service;
use AC\core\system\config\BaseConfig;

class TicketConfig extends BaseConfig
{
  public function refundMethodFullPrice(): bool
  {
    return (bool) Service::configDB('ticket', 'refund_method_full_price');
  }
}