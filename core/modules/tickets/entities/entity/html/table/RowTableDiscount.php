<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableDiscount extends RowTable
{
  public function generateItems($data =[], $new = true, $classItems = null): RowTableDiscount
  {
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Abo discount', 'tickets') . ':',
          'class'   => $classItems
        ],
      )
    );
    $discount_data = [lang('None')];
    if(Service::engines()->discount->getDiscounts(1, $discount)) {
      foreach ($discount as $d) {
        $discount_data[$d['discount_id']] =  StringHelper::shield($d['title'])
          . ' (Abo: ' . NumberHelper::format($d['ticket']) . ' ' . ($d['dimension'] == 1 ? '%' : CURR_VALUTE) . ')';
      }
    }
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' =>useLayout()::render('select', [
            'name'    => 'space',
            'values'  => $discount_data,
            'class'   => ' ',
            'current' => Service::request()->findByAlias('discount_id', $data, null, 'post')
          ], 'common'),
          'class'   =>  $classItems,
        ]
      )
    );

    return parent::generateItems();
  }

}