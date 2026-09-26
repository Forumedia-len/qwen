<?php

namespace AC\core\modules\payment\services;

use AC\core\modules\payment\config\payment\OnlinePaymentMethodsInterface;
use AC\core\modules\payment\config\payment\PaymentProfileInterface;
use AC\core\modules\payment\config\PayoneConfig;
use AC\core\modules\payment\config\PaypalConfig;
use AC\core\modules\payment\models\ProfileContextModel;
use AC\core\modules\payment\payone\entities\enums\OnlineGateway;
use Service;

/**
 * Выбор основной ветки онлайн-оплаты (PayPal или Payone).
 * Хранение: таблица config, type = payment, alias = online_gateway_primary.
 * Пока в БД ничего нет — legacy (Payone при USE_PAYONE_PAYMENT и заполненном pay.config).
 *
 * Чтение значения из БД: {@see Service::configDB()} (`payment`, `online_gateway_primary`).
 */
class OnlineGatewayService
{
  /**
   * Реестр провайдеров онлайн-оплаты.
   *
   * Добавление нового провайдера должно сводиться к:
   * - добавлению case в {@see OnlineGateway}
   * - добавлению записи в этот реестр (configKey + profileClass)
   *
   * @return array<string, array{title:string,profile:class-string<ProfileContextModel>,
   *   config:class-string<OnlinePaymentMethodsInterface>}>
   */
  private static function registry(): array
  {
    return [
      OnlineGateway::PAYPAL->value => [
        'title'  => lang('option_online_gateway_paypal', 'config'),
        'config' => self::paypalConfig(),
      ],
      OnlineGateway::PAYONE->value => [
        'title'  => lang('option_online_gateway_payone', 'config'),
        'config' => self::payoneConfig(),
      ],
    ];
  }

  /** @return array{title:string,profile:class-string<ProfileContextModel>,config:class-string<OnlinePaymentMethodsInterface>}|null */
  private static function registryItem(?string $gateway): ?array
  {
    $gateway = strtolower(trim((string)$gateway));
    if ($gateway === '') {
      return null;
    }
    return self::registry()[$gateway] ?? null;
  }

  public static function onlinePaymentUse(): bool
  {
    $v = Service::cast('boolish')->get(Service::configDB('payment', 'online_payment_use'), ['nullOnEmpty' => true, 'strictBool' => true]);
    if ($v !== null) {
      return $v;
    }
    // Legacy: раньше был только PayPal
    return (bool)Service::configDB('paypal', 'paypal_use');
  }

  /** Выбор админа + fallback на legacy, без учёта «сломанного» Payone */
  public static function primaryGateway(): ?string
  {
    static $gateway;
    if ($gateway === null) {
      $gateway = self::rawConfiguredPrimary();
    }

    return $gateway ?? null;
  }

  /**
   * Фактическая ветка для рантайма.
   *
   * Важно: если админ выбрал Payone, но он неработоспособен (профиль/реквизиты/методы),
   * то НЕ делаем фолбэк на PayPal, а возвращаем {@see OnlineGateway::NONE}.
   */
  public static function runtimeGateway(): string
  {
    $gateway = self::primaryGateway();
    if (self::onlinePaymentUse() && self::gatewayConfig($gateway)->isOperational()) {
      return $gateway;
    }

    return OnlineGateway::NONE->value;
  }

  /** Значение из БД или null, если не задано */
  private static function rawConfiguredPrimary(): ?string
  {
    if ($v = Service::configDB('payment', 'online_gateway_primary')) {
      $v = strtolower(trim((string)$v));
      return in_array($v, self::availableGateways(true), true) ? $v : null;
    }
    return OnlineGateway::PAYPAL->value;
  }

  /** Конфиг PayPal (единая точка доступа) */
  public static function paypalConfig(?array $params = []): PaypalConfig
  {
    static $config;
    if ($config === null || !empty($params)) {
      /** @var PaypalConfig $config */
      $config = config(OnlineGateway::PAYPAL->value)->initData($params);
    }
    return $config;
  }

  /** Конфиг Payone (единая точка доступа) */
  public static function payoneConfig(?array $params = []): PayoneConfig
  {
    static $config;
    if ($config === null || !empty($params)) {
      /** @var PayoneConfig $config */
      $config = config(OnlineGateway::PAYONE->value)->initData($params);
    }

    return $config;
  }

  public static function gatewayConfig(?string $gateway = null): ?PaymentProfileInterface
  {
    $gateway = $gateway ?? self::runtimeGateway();
    if (($itemGateway = self::registryItem($gateway)) !== [] && !empty($itemGateway['config']) && $itemGateway['config'] instanceof PaymentProfileInterface) {
      return $itemGateway['config'];
    }
    return null;
  }

  /**
   * Хвост `|tk:…` для `accounts.price_info` предоплаты: ключ как в `config.type`, без конфликта с `|`-сегментами PayPal/Payone.
   *
   * @param string|null $typeKey явный ключ; `null` — {@see self::prepaymentOnlineProfileTypeKey()} (Guthaben без площадки).
   */
  public static function encodePrepaymentPriceInfoTypeKeyTail(?string $typeKey = null): string
  {
    if ($typeKey === null) {
      $typeKey = self::gatewayConfig()->typeKey;
    }
    $typeKey = trim((string)$typeKey);
    if ($typeKey === '') {
      return '';
    }

    return '|tk:' . rawurlencode($typeKey);
  }

  public static function parseTypeKeyFromPrepaymentPriceInfo(?string $priceInfo): ?string
  {
    if ($priceInfo === null || $priceInfo === '') {
      return null;
    }
    if (preg_match('/\|tk:(.+)$/', $priceInfo, $m)) {
      $decoded = rawurldecode($m[1]);

      return $decoded !== '' ? $decoded : null;
    }

    return null;
  }

  /**
   * Разобрать платёжные данные онлайн-счёта из `accounts.price_info`.
   *
   * @return array{gateway: string, reference: string, transaction_id: string, payment_type: string|null, profile_type_key: string|null}|null
   */
  public static function parseOnlinePaymentInvoicePriceInfo(?string $priceInfo): ?array
  {
    $priceInfo = trim((string)$priceInfo);
    if ($priceInfo === '') {
      return null;
    }

    $profileTypeKey = self::parseTypeKeyFromPrepaymentPriceInfo($priceInfo);
    $corePriceInfo  = preg_replace('/\|tk:.+$/', '', $priceInfo) ?? $priceInfo;
    $parts          = explode('|', $corePriceInfo);
    $reference      = trim((string)($parts[1] ?? ''));
    $transactionId  = trim((string)($parts[2] ?? ''));
    if ($reference === '' || $transactionId === '') {
      return null;
    }

    $paymentType = trim((string)($parts[3] ?? ''));
    $gateway = (
      str_starts_with(strtolower((string)$profileTypeKey), OnlineGateway::PAYONE->value)
      || $paymentType !== ''
    ) ? OnlineGateway::PAYONE->value : OnlineGateway::PAYPAL->value;

    return [
      'gateway'          => $gateway,
      'reference'        => $reference,
      'transaction_id'   => $transactionId,
      'payment_type'     => $paymentType !== '' ? $paymentType : null,
      'profile_type_key' => $profileTypeKey,
    ];
  }

  /**
   * После NVP: сохранить суффикс `|tk:…` из уже записанного `price_info` (если был).
   */
  public static function mergePrepaymentPriceInfoWithExistingTypeKeyTail(string $newCorePriceInfo, ?string $existingPriceInfo): string
  {
    if ($existingPriceInfo !== null && $existingPriceInfo !== '' && preg_match('/(\|tk:.+)$/', $existingPriceInfo, $m)) {
      return $newCorePriceInfo . $m[1];
    }

    return $newCorePriceInfo;
  }

  public static function isSupportedGateway(?string $gateway = null): bool
  {
    return in_array($gateway ?? self::runtimeGateway(), self::availableGateways(true), true);
  }

  /**
   * @param string|null $gateway
   *
   * @return ProfileContextModel|null
   */
  public static function profile(?string $gateway = null): ?ProfileContextModel
  {
    if (($profileContextModel = self::getGatewayData($gateway ?? self::primaryGateway())['profile']) === null) {
      return useClass($profileContextModel, true);
    }
    return null;
  }

  /**
   * @param string|null $gateway
   *
   * @return array|null
   */
  public static function getGatewayData(?string $gateway = null): ?array
  {
    return self::registryItem($gateway ?? self::primaryGateway()) ?? null;
  }

  /**
   * Список поддерживаемых системой шлюзов онлайн-оплаты (валидные значения).
   *
   * @return list<string>
   */
  public static function availableGateways(?bool $asKeys = false): array
  {
    $gateways = self::registry();

    return $asKeys ? array_keys($gateways) : $gateways;
  }
}

