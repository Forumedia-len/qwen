<?php

namespace AC\core\modules\payment\config;

use AC\core\modules\payment\config\payment\OnlinePaymentMethodsInterface;
use AC\core\modules\payment\config\payment\PaymentProfile;
use AC\core\modules\payment\config\payment\PaymentProfileInterface;
use AC\core\modules\payment\payone\entities\enums\MethodPayment;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use AC\core\modules\payment\payone\models\PayoneProfileContext;

/**
 * Class PayoneConfig
 *
 * @property-read string $mid
 * @property-read string $aid
 * @property-read string $portalid
 * @property-read string $key
 * @property-read string $api_version
 * @property-read string $mode
 * @property-read string $encoding
 * @property-read string $useUrlCreation
 * @property-read string $param
 * @property-read bool   $payone_use
 * @property-read string $typeKey
 * @property-read array  $payone_methods
 * @property-read array  $payone_method_params
 */
class PayoneConfig extends PaymentProfile implements OnlinePaymentMethodsInterface
{

  public function initData(?array $params = []): PaymentProfileInterface
  {
    parent::initData($params);
    $actualMethods = [];
    foreach ($this->payone_methods as $payoneMethod) {
      if ($this->hasRequiredParamsForMethod($payoneMethod)) {
        $actualMethods[] = $payoneMethod;
      }
    }
    $this->payone_methods = $actualMethods;
    return $this;
  }

  public function useProfile(): bool
  {
    return $this->payone_use;
  }

  public function checkData(): bool
  {
    return !empty($this->mid) && !empty($this->aid) && !empty($this->portalid) && !empty($this->key);
  }

  public function isOperational(): bool
  {
    if (parent::isOperational() && !empty($this->enabledMethods())) {
      return true;
    }

    return false;
  }

  /**
   * Включённые в профиле Payone способы оплаты для конкретного контекста (type/sport).
   *
   * Хранение: config.type = payone / payone|..., alias = payone_methods (строка "cc;pp;...").
   *
   * Правила:
   * - если поле не задано — считаем, что доступны все методы из availableMethods().
   * - если задано — фильтруем по доступным методам системы.
   *
   * @return list<string> список кодов методов
   */
  public function enabledMethods(): array
  {
    return $this->payone_methods ?? [];
  }

  /**
   * Доп. параметры методов оплаты из профиля.
   *
   * @return array<string, mixed>
   */
  public function methodParams(?string $methodCode = null): array
  {
    return $this->payone_method_params[$methodCode] ?? [];
  }

  /**
   * portalid/key для исходящего запроса к Payone.
   * Override метода — только если в payone_method_params заданы оба поля.
   *
   * @return array{portalid:string, key:string}
   */
  public function portalCredentialsForMethod(?string $methodCode): array
  {
    if (!empty($methodCode)) {
      $params           = $this->methodParams($methodCode);
      $overridePortalid = trim((string)($params['portalid'] ?? ''));
      $overrideKey      = trim((string)($params['key'] ?? ''));
      if ($overridePortalid !== '' && $overrideKey !== '') {
        return ['portalid' => $overridePortalid, 'key' => $overrideKey];
      }
    }

    return [
      'portalid' => $this->portalid,
      'key'      => $this->key,
    ];
  }

  /**
   * Проверка key из Transaction Status (POST['key'] = md5(secret)).
   */
  public function verifyTransactionStatusKey(string $postedKeyMd5, ?string $portalId = null): bool
  {
    if (!empty($postedKeyMd5) && !empty($portalId)) {
      $credentials = $this->portalSigningCredentials();

      return array_key_exists($portalId, $credentials) && hash_equals($postedKeyMd5, md5($credentials[$portalId]));
    }

    return false;
  }

  /**
   * Секретные ключи по portalid (основной профиль + override методов).
   *
   * @return array<string, string> portalid => key
   */
  private function portalSigningCredentials(): array
  {
    static $credentials;
    if (empty($credentials)) {
      if (!empty($this->key) && !empty($this->portalid)) {
        $credentials[$this->portalid] = $this->key;
      }

      foreach (array_keys($this->availableMethods()) as $methodCode) {
        $params   = $this->methodParams($methodCode);
        $portalid = trim((string)($params['portalid'] ?? ''));
        $key      = trim((string)($params['key'] ?? ''));
        if ($portalid === '' || $key === '') {
          continue;
        }
        $credentials[$portalid] = $key;
      }
    }

    return $credentials ?? [];
  }

  private function hasRequiredParamsForMethod(string $code): bool
  {
    $meta = $this->availableMethods()[$code] ?? null;
    if (!is_array($meta)) {
      return false;
    }
    $extraParams = $meta['extra_params'] ?? [];
    if (!is_array($extraParams) || $extraParams === []) {
      return true;
    }

    $methodParams = $this->methodParams($code) ?? null;
    if (empty($methodParams)) {
      // Если метод требует доп.параметры, но их нет — метод недоступен
      return false;
    }
    foreach ($extraParams as $key => $rule) {
      if (!is_array($rule)) {
        continue;
      }
      if (empty($rule['required'])) {
        continue;
      }
      $v = $methodParams[$key] ?? '';
      if (!is_scalar($v) || trim((string)$v) === '') {
        return false;
      }
    }

    return true;
  }

  /**
   * Унифицированный рендер способов оплаты Payone (HTML) через layouts:
   * - `payone_methods` (site/balance)
   * - `payone_methods_table` (touch)
   *
   * Контексты (`$context`):
   * - `order_site`  — форма заказа (site): radio `prepayment`, значения `3_{code}`.
   * - `order_touch` — форма заказа (touch): табличная разметка.
   * - `balance`     — пополнение счёта: radio `pay_type`, значения `{code}`.
   *
   */
  public function renderMethods(string $context, ?int $sum = null): string
  {
    $layout           = $context === 'order_touch' ? 'payone_methods_table' : 'payone_methods';
    $methods          = $extraHtml = [];
    $availableMethods = $this->availableMethods();
    $selectedCode     = $this->enabledMethods()[0] ?? null;
    foreach ($this->enabledMethods() as $code) {
      if (array_key_exists($code, $availableMethods) && ($availableMethod = $availableMethods[$code])) {
        $icons = [];
        foreach ($availableMethod['icons'] as $icon) {
          $icons[] = [
            'src' => cdn_url(paths()->getAssetsDir($icon, 'common')),
            'alt' => $availableMethod['title'],
          ];
        }
        $methods[$code] = [
          'id'            => 'pay_type_' . preg_replace('/[^a-z0-9\-_]/i', '', $code),
          'icons'         => $icons,
          'name'          => $context === 'balance' ? 'pay_type' : 'prepayment',
          'value'         => htmlspecialchars(($context === 'balance' ? '' : '3_') . $code),
          'checked'       => (($sum === null || $sum > 0) && $selectedCode !== null && $selectedCode === $code) ? 'checked' : '',
          'comment_block' => $availableMethod['comment_block'],
        ];
      }
      if (isset($availableMethod['required_html']) && is_array($availableMethod['required_html'])) {
        foreach ($availableMethod['required_html'] as $aliasPath => $opts) {
          if (method_exists($this, $aliasPath)) {
            $extraHtml[] = $this->$aliasPath($opts);
          }
        }
      }
    }
    return view()->render($this->pathViews('methods/' . $layout), ['methods' => $methods, 'extraHtml' => $extraHtml]);
  }

  /**
   * Рендер device fingerprinting (Payla DCS) для метода bnpl (PAYONE Secured Direct Debit / PDD).
   *
   * Вставляет hidden `device_token` + JS/CSS сниппет, который кладёт токен в hidden поле.
   * Токен затем отправляется в Payone как `add_paydata[device_token]`.
   */
  private function paylaDeviceFingerprinting(array $opts = []): string
  {
    $code       = MethodPayment::BNPL->value;
    $bnpl       = $this->methodParams($code) ?? null;
    $partnerId  = trim((string)($bnpl['df_partner_id'] ?? ''));
    $merchantId = $this->mid;
    if ($partnerId === '' || $merchantId === '') {
      return '';
    }
    $environment = in_array($this->mode, ['live', 'prod', 'production'], true) ? 'p' : 't';
    $sessionPart = session_id();
    if (!is_string($sessionPart) || $sessionPart === '') {
      $sessionPart = 'no-session';
    }
    try {
      $nonce = bin2hex(random_bytes(8));
    } catch (\Throwable) {
      $nonce = (string)mt_rand(100000, 999999) . (string)time();
    }
    $snippetToken = $partnerId . '_' . $merchantId . '_' . $sessionPart . $nonce;

    return view()->render($this->pathViews('methods/bnpl/payla_dcs'), [
      'partnerId'    => $partnerId,
      'merchantId'   => $merchantId,
      'environment'  => $environment,
      'snippetToken' => $snippetToken,
      'nonce'        => $sessionPart . '_' . $nonce,
    ]);
  }


  /**
   * Способы оплаты Payone, которые поддерживаются системой.
   *
   * Коды совпадают с clearingtype, который передаётся в Payone:
   * - cc  (карта)
   * - pp (кошелёк, PayPal внутри Payone)
   *
   * @return array<string, array{title:string,icons:list<string>}>
   */
  public function availableMethods(): array
  {
    return $this->profileContext()->availableMethods() ?? [];
  }

  protected function useDefault(): array
  {
    uses('pay.config');
    return [
      'mid'                  => defined('PAYONE_MID') ? PAYONE_MID : '',
      'aid'                  => defined('PAYONE_AID') ? PAYONE_AID : '',
      'portalid'             => defined('PAYONE_PORTALID') ? PAYONE_PORTALID : '',
      'key'                  => defined('PAYONE_SKEY') ? PAYONE_SKEY : '',
      'api_version'          => defined('PAYONE_VERSION') ? PAYONE_VERSION : '3.11',
      'mode'                 => defined('PAYONE_MODE') ? PAYONE_MODE : 'live',
      'encoding'             => defined('PAYONE_ENCODING') ? PAYONE_ENCODING : 'UTF-8',
      'useUrlCreation'       => !defined('PAYONE_USE_URL_CREATION') || PAYONE_USE_URL_CREATION,
      'param'                => defined('PAYONE_PARAM') ? PAYONE_PARAM : '',
      'payone_use'           => defined('USE_PAYONE_PAYMENT') && USE_PAYONE_PAYMENT,
      'typeKey'              => OnlineGateway::PAYONE->value,
      'payone_methods'       => ['cc', 'pp'],
      'payone_method_params' => [],
    ];
  }

  /**
   * Основные редактируемые поля Payone-профиля + правила валидации/нормализации.
   */
  public function profileFields(): array
  {
    return array_merge(parent::profileFields(), [
      'api_version',
      'mode',
      'encoding',
      'useUrlCreation',
      'param',
      'typeKey',
    ]);
  }

  private function pathViews(?string $path): string
  {
    return 'payment/payone/views/' . ($path ?? '');
  }

  /**
   * @return PayoneProfileContext
   */
  public function profileContext(): PayoneProfileContext
  {
    /** @var PayoneProfileContext $profileContext */
    static $profileContext;
    if (empty($profileContext)) {
      $profileContext = useClass(PayoneProfileContext::class, true);
    }
    return $profileContext;
  }

  /**
   * @return string
   */
  protected function baseTypeKey(): string
  {
    return OnlineGateway::PAYONE->value ?? '';
  }
}
