<?php

namespace AC\core\modules\payment\helpers;

class PaymentHelper
{
  private static array $codesPaymentMethods = [
    'cash'            => 0, // наличные
    'invoice'         => 1, // счет
    'private_account' => 2, // личный внутренний счет
    'paypal'          => 3, // payPal
    'payone'          => 3, // payOne
    'card'            => 4  // карта
  ];
  
  public static function checkCodePaymentMethodByAlias($code, $alias): bool
  {
    return match (true) {
      $code === null || $alias === null                 => false,
      (int)$code === self::getCodePaymentMethod($alias) => true,
      default                                           => false,
      
    };
  }
  
  /**
   * Получить код платежной системы по алиасу
   * @param $alias
   * @return int|null
   */
  public static function getCodePaymentMethod($alias): ?int
  {
    return self::$codesPaymentMethods[$alias] ?? null;
  }
  
  /**
   * Получить алиас платежной системы по коду
   * @param $code
   * @return string|null
   */
  public static function getAliasPaymentMethod($code): ?string
  {
    if ($code !== null) {
      foreach (self::$codesPaymentMethods as $alias => $codePaymentMethod) {
        if ($codePaymentMethod === (int)$code) {
          return $alias;
        }
      }
    }
    
    
    return null;
  }
  
  public static function isPayPal($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'paypal');
  }
  
  public static function isCash($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'cash');
  }
  
  public static function isInvoice($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'invoice');
  }
  
  public static function isCard($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'card');
  }
  
  public static function isPayOne($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'payone');
  }
  
  /**
   * @param $code
   * @return bool
   */
  public static function isPrivateAccount($code): bool
  {
    return self::checkCodePaymentMethodByAlias($code, 'private_account');
  }
  
  public static function getLabelPaymentByCode($code): string
  {
    return self::getLabelPaymentByAlias(self::getAliasPaymentMethod($code));
  }
  
  public static function getLabelPaymentByAlias($alias): string
  {
    return match ($alias) {
      'cash'            => lang('Cash payment'),            // наличные
      'invoice'         => lang('Invoice payment'),         // счет
      'private_account' => lang('Private account payment'), // личный внутренний счет
      'paypal'          => lang('Paypal payment'),          // payPal
      'payone'          => lang('PayOne payment'),          // payOne
      'card'            => lang('Card payment'),            // карта
      default           => lang('Is unknown')
    };
  }
  
  /**
   * @param $alias
   * @return string
   */
  public static function getShortLabelPaymentByAlias($alias): string
  {
    return lang('short_label_payment_' . $alias, null, [], lang('short_label_payment_unk'));
  }
  
  /**
   * @param $code
   * @return string
   */
  public static function getShortLabelPaymentByCode($code): string
  {
    return self::getShortLabelPaymentByAlias(self::getAliasPaymentMethod($code));
  }
}