<?php

namespace AC\app\config;

use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\modules\config\helpers\ScopedConstantHelper;
use AC\core\system\config\BaseConfig;

class PaymentConfig extends BaseConfig
{
  public function defaultPaymentMethod(?string $contextKey = null): ?string
  {
    return ScopedConstantHelper::stringValue('DEFAULT_PAYMENT_METHOD', $contextKey);
  }

  public function usePaypalPayment($checkTable = true): bool
  {
    return (!defined('USE_PAYMENT_PAYPAL') || USE_PAYMENT_PAYPAL)
      && (!$checkTable || OnlineGatewayService::paypalConfig()->isOperational());
  }

  public function usePayonePayment(): bool
  {
    return OnlineGatewayService::onlinePaymentUse() && OnlineGatewayService::runtimeGateway() === OnlineGateway::PAYONE->value;
  }

  public function useInvoicePayment(): bool
  {
    return !defined('USE_PAYMENT_INVOICE') || USE_PAYMENT_INVOICE;
  }

  public function useCashPayment(): bool
  {
    return !$this->isPaymentMethodHidden('BR');
  }

  public function showPaypalPaymentMethod(?string $contextKey = null): bool
  {
    return OnlineGatewayService::runtimeGateway() === OnlineGateway::PAYPAL->value
      && $this->usePaypalPayment()
      && !$this->isPaymentMethodHidden('PP', $contextKey)
      && $this->checkingRulesForPaypalPaymentMethod();
  }

  public function showPayonePaymentMethod(?string $contextKey = null): bool
  {
    return $this->usePayonePayment()
      && !$this->isPaymentMethodHidden('PO', $contextKey);
  }

  /** Проверить есть ли правила для показа этого способа оплаты
   *
   * @return bool
   * @todo будет отдельная таблица со всеми правилами для критичных условий
   */
  public function checkingRulesForPaypalPaymentMethod(): bool
  {
    return true;
  }

  public function showPrivateAccountPaymentMethod(?array $client = null, ?string $contextKey = null): bool
  {
    return !$this->isPaymentMethodHidden('GH', $contextKey);
  }

  public function useOnlinePayment(): bool
  {
    if (OnlineGatewayService::onlinePaymentUse()
      && OnlineGatewayService::runtimeGateway() !== OnlineGateway::NONE->value
      && OnlineGatewayService::gatewayConfig()->isOperational()) {
      return true;
    }

    return false;
  }

  public function orderBlockMinute(): int
  {
    return (int)(defined('COUNT_MINUTE_FOR_PAYONE_PAY') && COUNT_MINUTE_FOR_PAYONE_PAY ? COUNT_MINUTE_FOR_PAYONE_PAY
      : (defined('COUNT_MINUTE_FOR_PAYPAL') && COUNT_MINUTE_FOR_PAYPAL ? COUNT_MINUTE_FOR_PAYPAL : 1));
  }

  public function useFullProcessing(): bool
  {
    return !defined('LOCAL_SERVER') || !LOCAL_SERVER || !defined('USE_LOCAL_FULL_PROCESSING_PAYPAL') || USE_LOCAL_FULL_PROCESSING_PAYPAL;
  }
  public function isPaymentMethodHidden(string $method, ?string $contextKey = null): bool
  {
    return in_array($method, ScopedConstantHelper::listValue('HIDE_PAYMENT_METHOD', $contextKey), true);
  }

}
