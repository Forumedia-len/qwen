<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableSpace extends RowTable
{
  public function generateItems($data =[], $new = true, $classItems = null): RowTableSpace
  {
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Regularity', 'tickets') . ':',
          'class'   => $classItems
        ],
      )
    );
    $spaces = ['1' => lang('Every week', 'tickets'), '2' => lang('Every n weeks', 'tickets', ['space' => 2])];
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => $new ? useLayout()::render('select', [
            'name'    => 'space',
            'values'  => $spaces,
            'class'   => ' ',
            'current' => Service::request()->findByAlias('space', $data, null, 'post')
          ], 'common')
          : $spaces[Service::request()->findByAlias('space', $data, null, 'post')],
          'class'   =>  $classItems,
        ]
      )
    );

    return parent::generateItems();
  }

}