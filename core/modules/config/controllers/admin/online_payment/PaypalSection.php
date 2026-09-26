<?php

namespace AC\core\modules\config\controllers\admin\online_payment;

use AC\core\modules\payment\paypal\models\PaypalProfileContext;

final readonly class PaypalSection extends PaymentProfileSection
{
  protected function contextModel(): string
  {
    return PaypalProfileContext::class;
  }
}
