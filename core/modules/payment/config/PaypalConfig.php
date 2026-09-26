<?php

namespace AC\core\modules\payment\config;

use AC\core\modules\payment\config\payment\PaymentProfile;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\paypal\models\PaypalProfileContext;

/**
 * @property-read string $api_username
 * @property-read string $api_password
 * @property-read string $api_signature
 * @property-read string $endpoint
 * @property-read string $api_url
 * @property-read string $api_version
 * @property-read bool   $paypal_use
 * @property-read string $typeKey
 */
class PaypalConfig extends PaymentProfile
{
  public string $useVersion = 'old';

  public function showInStats(): bool
  {
    return defined('SHOW_PAYPAL_IN_STAT') && (bool)SHOW_PAYPAL_IN_STAT;
  }

  public function useProfile(): bool
  {
    return $this->paypal_use;
  }

  public function checkData(): bool
  {
    return !empty($this->api_username) && !empty($this->api_password) && !empty($this->api_signature);
  }

  /**
   * Основные редактируемые поля PayPal-профиля + правила валидации/нормализации.
   *
   * @return array
   */
  public function profileFields(): array
  {
    return array_merge(parent::profileFields(), [
      'endpoint',
      'api_url',
      'api_version',
      'typeKey',
    ]);
  }

  /**
   * @return PaypalProfileContext
   */
  public function profileContext(): PaypalProfileContext
  {
    /** @var PaypalProfileContext $profileContext */
    static $profileContext;
    if ($profileContext === null) {
      $profileContext = useClass(PaypalProfileContext::class, true);
    }
    return $profileContext;
  }

  protected function useDefault(): array
  {
    uses('pp.config');
    return [
      'api_username'  => defined('API_USERNAME') ? API_USERNAME : '',
      'api_password'  => defined('API_PASSWORD') ? API_PASSWORD : '',
      'api_signature' => defined('API_SIGNATURE') ? API_SIGNATURE : '',
      'endpoint'      => defined('API_ENDPOINT') ? API_ENDPOINT : 'https://api-3t.paypal.com/nvp',
      'api_url'       => defined('PAYPAL_URL') ? PAYPAL_URL : 'http://www.paypal.com/webscr&cmd=_express-checkout&token=',
      'api_version'   => defined('VERSION') ? VERSION : '53.0',
      'paypal_use'    => defined('USE_PAYMENT_PAYPAL') && USE_PAYMENT_PAYPAL,
      'typeKey'       => OnlineGateway::PAYPAL->value,
    ];
  }

  /**
   * @return string
   */
  protected function baseTypeKey(): string
  {
    return OnlineGateway::PAYPAL->value ?? '';
  }
}