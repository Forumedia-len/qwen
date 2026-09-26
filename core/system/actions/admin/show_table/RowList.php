<?php

namespace AC\core\system\actions\admin\show_table;

use AC\app\helpers\ButtonLinkLayoutHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\object\entity\html\table\FieldTable;

class RowList
{
  protected array $data = [];

  /**
   * @param $data
   * @param $fields
   * @return array
   */
  public function getRow($data, $fields): array
  {
    $this->data = $data;
    $row        = [];
    /** @var FieldTable $field */
    foreach ($fields as $field) {
      $itemRow = ObjectHelper::createObject(['value', 'class', 'id', 'style']);
      $itemRow->addProperty('field', $field);
      $row[] = $this->getItem($field, $itemRow);
    }

    return $row;
  }

  protected function getItem(FieldTable $field, $ItemRow)
  {
    $nameField = ucfirst(StringHelper::underscoreToCamelCase($field->getName()));
    if (method_exists($this, 'get' . $nameField)) {
      return $this->{'get' . ucfirst($nameField)}($ItemRow);
    }
    if (isset($this->data[$field->getName()])) {
      $ItemRow->value = $this->data[$field->getName()];
    }

    return $ItemRow;
  }

  /**
   * @param object{'value': string, 'class' : string, 'id' : string, 'style' :string, 'field': FieldTable} $ItemRow
   * @return mixed
   */
  protected function getMark(object $ItemRow): object
  {
    $ItemRow->class = 'light';
    $ItemRow->style = 'text-align:center';
    $ItemRow->value = useLayout()->render('checkbox', [
      'field' => ObjectHelper::createObject([
        'name'  => $ItemRow->field->checkbox['name'] . '[]',
        'value' => $this->data[$ItemRow->field->checkbox['id']]
      ], true)
    ], 'admin');

    return $ItemRow;
  }


  protected function getActions($ItemRow, $separator = '&nbsp;&nbsp;')
  {
    $ItemRow->class = 'dark';
    $ItemRow->style = 'white-space: nowrap';
    $ItemRow->value = implode($separator, $this->getButtonsAction());

    return $ItemRow;
  }

  protected function getButtonsAction()
  {
    return [
      ButtonLinkLayoutHelper::getEditButton($this->data['actions']['edit']),
      ButtonLinkLayoutHelper::getRemoveButton($this->data['actions']['remove']),
    ];
  }
}