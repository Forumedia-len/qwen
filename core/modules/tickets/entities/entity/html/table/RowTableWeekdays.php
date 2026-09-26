<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\object\entity\html\Table;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableWeekdays extends RowTable
{
  public function generateItems($data = [], $new = true, $classItems = null): RowTableWeekdays
  {
    if(isset($data['weekdays']) && !is_array($data['weekdays'])) {
      $data['weekdays'] = explode(',', $data['weekdays']);
    }
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Weekdays') . ':',
          'class'   => $classItems
        ],
      )
    );
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => $new ? $this->generateDateTable($data)->asString() : $this->getActualWeekdays($data),
          'class'   => $classItems,
        ]
      )
    );

    return parent::generateItems();
  }

  protected function generateDateTable($data = []): Table
  {
    $table = HtmlHelper::table();
    for ($i = 0; $i <= 6; $i++) {
      $table->addRow(HtmlHelper::tagTr()
        ->addItem(HtmlHelper::tagTdTh('td', null, null, ['content' => TranslateHelper::translateWeekday($i)]))
        ->addItem(HtmlHelper::tagTdTh('td', null, null,
          [
            'content' => ObjectHelper::createEntity(
              'html\form\FieldForm',
              null,
              'weekdays[' . $i . ']',
              ['type' => 'checkbox', 'value' => 1])->addOtherAttribute('checked', in_array($i, Service::request()->findByAlias('weekdays', $data, [], 'post')))->asString()
          ])));
    }
    return $table;
  }

  protected function getActualWeekdays($data = []): string
  {
    $out = [];
    for ($i = 0; $i < 7; $i++) {
      if (in_array($i, Service::request()->findByAlias('weekdays', $data, [], 'post'))) {
        $out[]  = TranslateHelper::translateWeekday($i);
      }
    }

    return implode('<br>', $out);
  }


}