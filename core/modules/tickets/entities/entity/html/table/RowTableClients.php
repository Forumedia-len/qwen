<?php

namespace AC\core\modules\tickets\entities\entity\html\table;

use AC\app\helpers\LayoutHelper;
use AC\core\system\helpers\HtmlHelper;
use AC\core\system\object\entity\html\table\RowTable;
use Service;

class RowTableClients extends RowTable
{

  public function generateItems($data = [], $new = true, $classItems = null): RowTableClients
  {
    Service::engines()->clients->getClientsData('1,2', 1, $clients_data, 'surname, name', null, 'client_id, surname, name');
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        ['content' => lang('Client') . ':', 'class' => $classItems],
      )
    );
    $this->addItem(
      HtmlHelper::tagTdTh('td', null, null,
        [
          'content' => LayoutHelper::renderListClientsBySelect(
            'client_id',
            $clients_data,
            Service::request()->findByAlias('client_id', $data, 0, 'post')),
          'class'   => $classItems
        ]
      )
    );

    return parent::generateItems();
  }
}