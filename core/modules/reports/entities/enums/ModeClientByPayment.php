<?php

namespace AC\core\modules\reports\entities\enums;

use AC\core\modules\payment\services\OnlineGatewayService;

enum ModeClientByPayment: string
{

  /** '1_1' -  Онлайн клиенты платят по счету | Online Kunden mit Login (Rechnung)
   *  '1_0' -  Онлайн клиенты платят наличкой | Online Kunden mit Login (Barzahlung)
   *  '1_2' -  Онлайн клиенты платят с внутреннего счета | Online Kunden mit Login (Guthaben)
   *  '1_3' -  Онлайн клиенты платят через онлайн систему, оплаты (paypal или payone) | Pay online
   *  '1_4' -  Онлайн клиенты платят карточкой намести | EC
   *  '2_0' -  Офлайн клиенты платят наличкой | Offline Kunden ohne Login - не используются
   *  '3_0' -  Гости платят через наличку | Bar-Zahler ohne Login
   *  '3_3' -  Гости платят через онлайн систему, оплаты (paypal или payone) | Bar-Zahler ohne Login
   */
  case OnlineInvoice = '1_1';
  case OnlineCash = '1_0';
  case OnlinePrivateAccount = '1_2';
  case OnlinePayOnline = '1_3';
  case OnlineCard = '1_4';
//  case OfflineCash          = '2_0';
  case GuestPayOnline = '3_3';

  case GuestCash      = '3_0';

  public function titleReport(): string
  {
    return match ($this->value) {
      '1_1'        => lang('Online customers with login (invoice)', 'reports'),
      '1_0'        => lang('Online customers with login (cash payment)', 'reports'),
      '1_2'        => lang('Online customers with login (private account)', 'reports'),
      '1_3'        => lang('Online customers with login (pay online)', 'reports'),
      '1_4'        => lang('EC', 'reports'),
      '2_0'        => lang('Offline clients without login ', 'reports'),
      '3_3', '3_0' => lang('Cash payer without login', 'reports'),
    };
  }

  public static function withdrawalOrder(): array
  {
    return [
      '1_1' => self::OnlineInvoice,
      '1_0' => self::OnlineCash,
      '1_2' => self::OnlinePrivateAccount,
      '1_3' => self::OnlinePayOnline,
      '1_4' => self::OnlineCard,
//      '2_0' => self::OfflineCash,
      '3_3' => self::GuestPayOnline,
      '3_0' => self::GuestCash,
    ];
  }

  public function usePayment(): bool
  {
    return match (true) {
      $this === self::OnlineInvoice
      && !config('payment')->useInvoicePayment()   => false,
      $this === self::OnlinePayOnline
      && ((!OnlineGatewayService::paypalConfig()->showInStats()
          && config('payment')->usePaypalPayment())
        || !config('payment')->useOnlinePayment()) => false,
      default                                      => true,
    };
  }

}


