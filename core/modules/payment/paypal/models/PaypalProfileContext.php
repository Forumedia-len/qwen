<?php

namespace AC\core\modules\payment\paypal\models;

use AC\core\modules\config\models\ConfigDbValueSelector;
use AC\core\modules\payment\models\ProfileContextModel;

/**
 * Доп. реквизиты PayPal по контексту площадки: строки в {prefix}config с type вида
 * {prefix}|{alias}[_{type_id}[_{sport_id}[_{area_id}]]]; чтение значений через {@see ConfigDbValueSelector}.
 */
class PaypalProfileContext extends ProfileContextModel
{
  public const CORRELATION_PREFIX = 'paypal';

  /**
   * Редактируемые поля PayPal-профиля + правила валидации/нормализации.
   *
   * Единственный источник истины о составе полей: используется в контроллере,
   * модели (выборка из БД) и везде, где нужен список полей.
   *
   * @return array
   */
  public static function profileFields(): array
  {
    return [
      'api_username'  => ['type' => 'string', 'required' => true,  'trim' => true],
      'api_password'  => ['type' => 'string', 'required' => true,  'trim' => false, 'masked' => [2, 2]],
      'api_signature' => ['type' => 'string', 'required' => true,  'trim' => true,  'masked' => [7, 7]],
      'paypal_use'    => ['type' => 'bool',   'required' => false, 'trim' => false],
    ];
  }
}
