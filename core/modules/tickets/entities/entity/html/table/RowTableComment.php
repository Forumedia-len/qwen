<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\core\system\helpers\HtmlHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableComment extends RowTable
{
  public function generateItems($data = [], $new = true, $classItems = null): RowTableComment
  {
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => lang('Comment') . ':',
          'class' => $classItems
        ],
      )
    );
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => ObjectHelper::createEntity(
            'html\form\FieldForm',
            null,
            'title',
            ['type' => 'text', 'value' => Service::request()
              ->findByAlias('title', $data, '', 'post'), 'class' => 'input small'])
            ->addOtherAttribute('maxlength', 255)
            ->asString()
          ,
          'class' => $classItems,
        ]
      )
    );

    return parent::generateItems();
  }

}