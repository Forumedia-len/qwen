<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableTime extends RowTable
{
  public function generateItems($data = [], $new = true, $classItems = null): RowTableTime
  {
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Time') . ':',
          'class'   => $classItems
        ],
      )
    );
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => $new ?
            ' '
            : TimeHelper::convertTime24(Service::request()->findByAlias('time_start', $data, null, 'post'), false) .
            ' - ' . TimeHelper::convertTime24(Service::request()->findByAlias('time_finish', $data, null, 'post'), false),
          'class'   => $classItems,
          'id'      => 'loadTimeBlock'
        ]
      )
    );

    return parent::generateItems();
  }

}