<?php

namespace AC\core\modules\reservations\services;

use AC\app\config\PaymentConfig;
use AC\app\entities\enums\Encash;
use AC\core\modules\config\helpers\ScopedConstantHelper;
use AC\core\modules\reservations\entities\PaymentMethodContext;
use AC\core\modules\reservations\entities\PaymentMethodsState;

/**
 * Resolves payment method visibility, availability, and default selection.
 */
final class PaymentMethodResolver
{
  public function __construct(private PaymentConfig $paymentConfig)
  {
  }

  public function resolve(PaymentMethodContext $context): PaymentMethodsState
  {
    $contextKey = ScopedConstantHelper::contextKey($context->typeId, $context->sportId);
    $visible    = $this->resolveVisible($context, $contextKey);
    $selectable = $this->resolveSelectable($context, $visible);
    $selected   = $this->resolveSelected($context, $selectable, $contextKey);

    return new PaymentMethodsState($visible, $selectable, $selected);
  }

  /** @return array<string, bool> */
  private function resolveVisible(PaymentMethodContext $context, ?string $contextKey): array
  {
    $cashAlias           = Encash::Cash->shortAlias();
    $invoiceAlias        = Encash::Invoice->shortAlias();
    $privateAccountAlias = Encash::PrivateAccount->shortAlias();

    return [
      $cashAlias           => !$this->paymentConfig->isPaymentMethodHidden($cashAlias, $contextKey)
        && (($context->barClient && in_array(THE_GUEST_CAN_PLAY_IN_CASH, [$context->channel, 'all'], true))
          || (!$context->barClient && $context->clientMethod === Encash::Cash)),
      $invoiceAlias        => !$context->barClient
        && $context->clientMethod === Encash::Invoice
        && !$this->paymentConfig->isPaymentMethodHidden($invoiceAlias, $contextKey),
      $privateAccountAlias => !$context->barClient
        && $this->paymentConfig->showPrivateAccountPaymentMethod(null, $contextKey),
      'PP'                 => $this->paymentConfig->showPaypalPaymentMethod($contextKey),
      'PO'                 => $this->paymentConfig->showPayonePaymentMethod($contextKey),
    ];
  }

  /** @param array<string, bool> $visible
   *  @return array<string, bool>
   */
  private function resolveSelectable(PaymentMethodContext $context, array $visible): array
  {
    $selectable         = $visible;
    $privateAccountAlias = Encash::PrivateAccount->shortAlias();
    $selectable[$privateAccountAlias] = $visible[$privateAccountAlias]
      && ($context->privateAccountBalance > 0 || $context->clientMethod === Encash::PrivateAccount);
    $selectable['PP'] = $visible['PP'] && $context->price > 0;
    $selectable['PO'] = $visible['PO'] && $context->price > 0;

    return $selectable;
  }

  /** @param array<string, bool> $selectable */
  private function resolveSelected(
    PaymentMethodContext $context,
    array $selectable,
    ?string $contextKey
  ): ?string {
    if ($context->barClient && $context->price > 0 && ($selectable['PP'] ?? false)) {
      return 'PP';
    }

    $availableAliases = array_keys(array_filter($selectable));
    if ($context->price > 0 && $availableAliases === ['PP']) {
      return 'PP';
    }

    $defaultMethod = $this->paymentConfig->defaultPaymentMethod($contextKey);
    if ($defaultMethod !== null && ($selectable[$defaultMethod] ?? false)) {
      return $defaultMethod;
    }

    $clientAlias = $context->clientMethod->shortAlias();
    $clientMethodSelected = match ($context->clientMethod) {
      Encash::Cash           => !$context->barClient || $context->price === 0.0,
      Encash::Invoice        => !$context->barClient,
      Encash::PrivateAccount => !$context->barClient,
      Encash::PayOnline      => $context->barClient && $context->price > 0,
      default                => false,
    };

    return $clientMethodSelected && ($selectable[$clientAlias] ?? false) ? $clientAlias : null;
  }
}
