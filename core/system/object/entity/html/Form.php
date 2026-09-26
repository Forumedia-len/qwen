<?php

namespace AC\core\system\object\entity\html;

use AC\app\helpers\ButtonLayoutHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\html\form\ButtonForm;
use AC\core\system\object\entity\html\form\FieldForm;
use AC\core\system\object\entity\html\table\FieldTable;

class Form extends TagHtml
{
  public string $method = 'post';
  public string $action;

  /**
   * @var FieldForm[]
   */
  protected array $fields = [];
  /**
   * @var ButtonForm[]
   */
  protected array $buttons = [];

  public bool $autocomplete = true;
  public bool $novalidate   = true;
  /**
   * @var string{'application/x-www-form-urlencoded', 'multipart/form-data', 'text/plain'}
   */
  public string $enctype;
  public string $accept_charset;

  public function getAttributes(): array
  {
    return array_merge(['name', 'action', 'method'], parent::getAttributes());
  }

  public function getButtonsAsString(): string
  {
    $buttons = '';

    foreach ($this->buttons as $button) {
      $buttons .= ButtonLayoutHelper::getInputButton($button);
    }

    return $buttons;
  }

  public function generateField($name, $properties = []): FieldForm
  {
    return ObjectHelper::createEntity('html\form\FieldForm', null, $name, $properties);
  }

  public function addField($name, $properties = []): Form
  {
    $this->fields[$name] = $this->generateField($name, $properties);

    return $this;
  }

  public function getField($name): FieldForm
  {
    if (!isset($this->fields[$name])) {
      $this->addField($name);
    }

    return $this->fields[$name];
  }

  public function addButton($name, $properties = []): Form
  {
    $this->buttons[$name] = $this->generateButton($name, $properties);

    return $this;
  }

  public function generateButton($name, $properties = [])
  {
    return ObjectHelper::createEntity('html\form\ButtonForm', null, $name, $properties);
  }

  /**
   * @return FieldForm[]
   */
  public function getHiddenFields(): array
  {
    $hidden = [];

    foreach ($this->fields as $field) {
      if ($field->type == 'hidden') {
        $hidden[] = $field;
      }
    }

    return $hidden;
  }

  public function getHiddenFieldsAsString(): string
  {
    $hidden = [];

    foreach ($this->getHiddenFields() as $field) {
      $hidden[] = $field->asString();
    }

    return implode("\n", $hidden);
  }

  public function asString(): string
  {
    return useLayout()->render('html\form', ['form' => $this], 'common');
  }
}