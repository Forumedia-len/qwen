<?php

namespace AC\core\modules\tickets\actions\admin\show_table;

use AC\core\system\actions\admin\show_table\ShowList;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\helpers\TranslateHelper;
use AC\core\system\object\entity\html\Form;
use AC\core\system\object\entity\html\Table;

class ShowListTickets extends ShowList
{
  protected ?int  $areaId;
  protected ?int  $mode;
  protected array $actions;

  /**
   * @return Table
   */
  protected function getTitlesForTableList(): Table
  {
    $fields = parent::getTitlesForTableList();
    if ($this->mode == 2) {
      $formFieldMark = ObjectHelper::createObject([
        'name'            => 'all_select',
        'value'           => 0,
        'checked'         => false,
        'id'              => 'checkbox_all_select',
        'otherProperties' => ' onchange="selectAll(this, \'tickets_list\')" ',
        'labelName'       => lang('To mark'),
      ], true);
      $fields->addField('mark', [
        'title'    => useLayout()::render('checkbox', ['field' => $formFieldMark], 'admin')
          . ' ' . useLayout()::render('label', ['field' => $formFieldMark], 'admin'),
        'checkbox' => ['name' => $this->getModel()->get_Name(), 'id' => $this->getModel()->getPrimaryKey()]
      ]);
    }

    if ($this->areaId === null) {
      $fields->addField('place', ['title' => lang('Place')]);
    }
    $fields->addField('client', ['title' => lang('Client')]);
    $fields->addField('period_date', ['title' => lang('Time period')]);
    $fields->addField('period_time', ['title' => lang('Time in hours')]);

    for ($weekDay = 0; $weekDay < 7; $weekDay++) {
      $fields->addField('week_day', ['title' => TranslateHelper::translateWeekday($weekDay, true), 'weekDay' => $weekDay]);
    }
    $fields->addField('space', ['title' => lang('Regularity', 'tickets')]);
    $fields->addField('discount', ['title' => lang('Discount(Customers | Subscription)', 'tickets')]);
    $fields->addField('comment', ['title' => lang('Comment')]);
    $fields->addField('actions', ['title' => lang('Action')]);

    return $fields;
  }

  protected function getForm(): Form
  {
    $form = parent::getForm();
    if ($this->mode == 2) {
      $form->id = 'tickets_list';
      $form->action = 'tickets/mode/2';
      $form->addButton('archives', [
        'type'            => 'button',
        'class'           => 'button',
        'value'           => lang('button_remove'),
        'otherAttributes' => ['onClick' => 'return buttonAction(\'tickets_list\', \'' . $this->actions['removeAll'] . '\')']
      ]);
    }

    return $form;
  }

  protected function getFooter(): object
  {
    $footer = parent::getFooter();
    if ($this->mode == 2) {
      $footer->buttons = $this->getForm()->getButtonsAsString();
    }

    return $footer;
  }
}