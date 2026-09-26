<?php

namespace AC\core\modules\specPrices\engines;

use AC\core\engines\TriggerConditionsEngine as BaseTriggerConditionsEngine;

class SpecPricesTriggerConditionsEngine extends BaseTriggerConditionsEngine
{
  protected string $typeBlock = 'specPrice';
  
  public function getAvailableCondition(): array
  {
    return [
      'do_not_show_on_account'  => [
        'description' => lang('If you select this booking condition with this special price, it will not be used to generate an Invoice.',
          'trigger_conditions'),
        'title'       => lang('Do not show on account', 'trigger_conditions'),
        'rules'        => ['bool'],
        'typeForm'    => 'checkbox',
        'checked'     => false,
        'useAlways'   => true,
        'value'       => 0,
      ],
    ];
  }
}