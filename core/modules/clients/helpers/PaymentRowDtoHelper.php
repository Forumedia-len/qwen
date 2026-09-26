<?php

namespace AC\core\modules\clients\helpers;

use AC\core\modules\clients\entities\dto\PrivateAccountTransactionDto;
use AC\core\modules\payment\helpers\PaymentHelper;
use AC\core\system\helpers\NumberHelper;

class PaymentRowDtoHelper
{
  public static function date($dateCreate): string
  {
    return date('d.m.Y H:i:s', strtotime($dateCreate));
  }
  
  public static function amount($amount): string
  {
    return NumberHelper::format($amount, 2, ',', ' ') . ' ' . CURR_VALUTE;
  }
  
  public static function label(PrivateAccountTransactionDto $transaction): string
  {
    return match ($transaction->type_code) {
      'change_payment_method' => self::langLabel($transaction->type_code,
        [
          'from' => /*'<strong>' . */ PaymentHelper::getLabelPaymentByAlias($transaction->related_data['from_encash'])/* . '</strong>'*/,
          'to'   => /*'<strong>' . */ PaymentHelper::getLabelPaymentByAlias($transaction->related_data['to_encash'])/* . '</strong>'*/,
        ]),
      default                 => self::langLabel($transaction->type_code),
      
    };
  }
  
  public static function getComment($type_code, $relateData): string
  {
    return match ($type_code) {
      'reservation_created_private_account',
      'reservation_removed_private_account',
      'reservation_removed_payone',
      'reservation_removed_paypal',
      'change_amount',
      'change_payment_method',
      'ticket_removed'  => self::getTitleArea($relateData['area_id']) . ' | '
        . date('d.m.Y', strtotime($relateData['date'])) . ' ' . $relateData['time'],
      'coupon_applied'  => $relateData['title'] . ' | ' . $relateData['code'],
      'invoice_deposit' => config('accountView')->getNumberAccountById($relateData['account_id'], PREPAYMENT_ACCOUNT_NUMBER),
      'paypal_deposit',
      'payone_deposit'  => config('accountView')->getNumberAccountById($relateData['account_id'], PREPAYMENT_ACCOUNT_NUMBER) . ' | '
        . lang('paymentAmount', 'payment_pp') . ' ' . self::amount($relateData['amount_real'])
        . ' 🠆 '
        . lang('transferAmount', 'payment_pp') . ' ' . self::amount($relateData['amount_private_account']),
      default           => '',
    };
  }
  
  public static function langLabel($type_code, $params = []): string
  {
    return lang($type_code, 'transaction_label', $params);
  }
  
  public static function getTitleArea($areaId): string
  {
    
    return getEngine('areas', false)?->getTitleByAreaId($areaId, 'title', true);
  }
}
