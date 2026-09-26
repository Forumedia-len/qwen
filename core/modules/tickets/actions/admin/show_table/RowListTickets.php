<?php

namespace AC\core\modules\tickets\actions\admin\show_table;

use AC\app\helpers\ButtonLinkLayoutHelper;
use AC\core\system\actions\admin\show_table\RowList;
use AC\core\system\helpers\TimeHelper;

class RowListTickets extends RowList
{

  protected function getPlace(object $ItemRow): object
  {
    $today          = strtotime(date('Y-m-d'));
    $ItemRow->class = 'dark';
    if (empty($this->data['date_finish']) || $today > strtotime(
        $this->data['date_finish'])) {
      $ItemRow->style = 'color:gray';
    }
    $ItemRow->value = $this->data['place'];

    return $ItemRow;
  }

  protected function getPeriodDate(object $ItemRow): object
  {
    $today          = strtotime(date('Y-m-d'));
    $ItemRow->class = 'dark';
    if ($this->data['date_start'] === null) {
      $ItemRow->value = lang('The time periods are not entered!', 'message_error');
    } else {
      if (empty($this->data['date_finish']) || $today > strtotime(
          $this->data['date_finish'])) {
        $ItemRow->style = 'color:gray';
      }
      $ItemRow->value = date('d.m.Y', strtotime($this->data['date_start']))
        . ' - ' . (!empty($this->data['date_finish']) ? date('d.m.Y', strtotime($this->data['date_finish'])) : '');
    }


    return $ItemRow;
  }

  protected function getPeriodTime(object $ItemRow): object
  {
    $today          = strtotime(date('Y-m-d'));
    $ItemRow->class = 'light';
    if (empty($this->data['date_finish']) || $today > strtotime(
        $this->data['date_finish'])) {
      $ItemRow->style = 'color:gray';
    }
    $ItemRow->value = TimeHelper::convertTime24($this->data['time_start'], false) . ' - ' . TimeHelper::convertTime24($this->data['time_finish'],
        false);

    return $ItemRow;
  }

  protected function getWeekDay(object $ItemRow): object
  {
    $j              = $ItemRow->field->weekDay;
    $ItemRow->class = $j % 2 == 0 ? 'dark' : 'light';
    $ItemRow->style = 'text-align:center';
    $ItemRow->value = str_contains($this->data['weekdays'], (string)$j) ? 'x' : '';

    return $ItemRow;
  }

  protected function getSpace(object $ItemRow): object
  {
    $ItemRow->class = 'light';
    $ItemRow->value = ($this->data['space'] == 1
      ? lang('Every week', 'tickets')
      : lang('Every n weeks', 'tickets', ['space' => $this->data['space']]));

    return $ItemRow;
  }

  protected function getClient(object $ItemRow): object
  {
    $ItemRow->class = 'light';
    $ItemRow->value = $this->data['client'];

    return $ItemRow;
  }

  protected function getDiscount(object $ItemRow): object
  {
    $ItemRow->class = 'dark';
    $ItemRow->value = $this->data['discount'];

    return $ItemRow;
  }

  protected function getComment(object $ItemRow): object
  {
    $ItemRow->class = 'light';
    $ItemRow->value = $this->data['title'];

    return $ItemRow;
  }

  protected function getButtonsAction(): array
  {

    return array_merge(
      [ButtonLinkLayoutHelper::getEditButton($this->data['actions']['viewInfo'], lang('Calculation', 'tickets'))],
      parent::getButtonsAction()
    );
  }

}