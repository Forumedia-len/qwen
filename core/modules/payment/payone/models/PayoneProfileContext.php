<?php

namespace AC\core\modules\payment\payone\models;

use AC\app\config\LangConfig;
use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\modules\payment\models\ProfileContextModel;
use AC\core\modules\payment\payone\entities\enums\MethodPayment;
use Service;

/**
 * Доп. реквизиты Payone по контексту площадки: строки в {prefix}config с type вида
 * {prefix}|{alias}[_{type_id}[_{sport_id}[_{area_id}]]]; чтение значений через {@see ConfigDbValueSelector}.
 */
class PayoneProfileContext extends ProfileContextModel
{
  public const CORRELATION_PREFIX = 'payone';

  public function __construct()
  {
    Service::lang()->addFile('', ('payone\\' . paths()->getLangDir()));
  }

  /**
   * Редактируемые поля Payone-профиля + правила валидации/нормализации.
   *
   * Единственный источник истины о составе полей: используется в контроллере,
   * модели (выборка из БД) и везде, где нужен список полей.
   */
  public static function profileFields(): array
  {
    return [
      'mid'                  => ['type' => 'string', 'required' => true, 'trim' => true],
      'aid'                  => ['type' => 'string', 'required' => true, 'trim' => true],
      'portalid'             => ['type' => 'string', 'required' => true, 'trim' => true],
      'key'                  => ['type' => 'string', 'required' => true, 'trim' => false, 'masked' => [2, 2]],
      'payone_use'           => ['type' => 'bool', 'required' => false, 'trim' => false],
      'payone_methods'       => ['type' => 'string', 'required' => false, 'trim' => true, 'useType' => 'separator'],
      'payone_method_params' => ['type' => 'string', 'required' => false, 'trim' => true, 'useType' => 'json', 'useCastParams' => ['array'], 'availableMethods' => true],
    ];
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
  public static function availableMethods(): array
  {
    return [
      MethodPayment::CARD->value   => [
        'title' => MethodPayment::CARD->label(),
        'icons' => [
          'images/icon/pay/visa.png',
          'images/icon/pay/mastercard.png',
          'images/icon/pay/americanexpress.png',
        ],
      ],
      MethodPayment::PAYPAL->value => [
        'title' => MethodPayment::PAYPAL->label(),
        'icons' => [
          'images/icon/pay/paypal.png',
        ],
      ],
      MethodPayment::WERO->value   => [
        'title' => MethodPayment::WERO->label(),
        'icons' => [
          'images/icon/pay/wero.png',
        ],
      ],
      MethodPayment::BNPL->value   => [
        'title'         => MethodPayment::BNPL->label(),
        'icons'         => [
          'images/icon/pay/bnpl.svg',
        ],
        'required_html' => [
          'paylaDeviceFingerprinting' => [],
        ],
        // Доп. параметры метода (хранятся в payone_method_params как { "bnpl": { ... } })
        'extra_params'  => [
          'df_partner_id' => [
            'type'     => 'string',
            'required' => true,
            'masked'   => [2, 2],
          ],
          'portalid'      => [
            'type'     => 'string',
            'required' => false,
          ],
          'key'           => [
            'type'     => 'string',
            'required' => false,
            'masked'   => [2, 2],
          ],
        ],
        'comment_block' => view()->render(self::pathViews('methods/bnpl/comment_block')),
      ],
    ];
  }

  public static function pathViews(?string $path): string
  {
    return 'payment/payone/views/' . ($path ?? '');
  }
}

