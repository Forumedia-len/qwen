<?php

namespace AC\app\config;

use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\config\BaseConfig;
use Service;

/** Настройки формирования счетов. */
class AccountConfig extends BaseConfig
{
  /** Доступно ли формирование SEPA. */
  public function useSEPA(): bool
  {
    return !defined('USE_SEPA') || USE_SEPA;
  }

  /** Создавать ли счета после успешной онлайн-оплаты через PayPal или Payone. */
  public function useOnlinePaymentInvoice(): bool
  {
    return OnlineGatewayService::onlinePaymentUse()
      && (int)Service::configDB('account', 'online_payment_invoice_use') === 1;
  }
}
