<?php

namespace AC\core\modules\payment\payone\entities\enums;

enum MethodPayment: string
{
  case CARD   = 'cc';
  case PAYPAL = 'pp';
  case WERO   = 'wero';
  case BNPL   = 'bnpl';

  public function shortLabel(): string
  {
    return match ($this) {
      self::CARD   => 'Card',
      self::PAYPAL => 'Paypal',
      self::WERO   => 'Wero',
      self::BNPL   => 'BNPL',
      default      => 'None',
    };
  }

  public function label(): string
  {
    return lang('option_payone_method_' . $this->value, 'config_online_payment');
  }
}
