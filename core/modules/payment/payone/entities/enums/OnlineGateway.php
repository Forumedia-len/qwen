<?php

namespace AC\core\modules\payment\payone\entities\enums;

enum OnlineGateway: string
{
  /**
   * Онлайн-оплата включена, но выбранный провайдер неработоспособен для рантайма.
   * Используется для UI/рантайм-ветвления без фолбэка на другой провайдер.
   */
  case NONE = 'none';

  case PAYPAL = 'paypal';

  case PAYONE = 'payone';
}
