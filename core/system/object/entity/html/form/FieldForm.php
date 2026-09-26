<?php

namespace AC\core\system\object\entity\html\form;

use AC\core\system\object\entity\html\ElementHtml;

class FieldForm extends ElementHtml
{
  /**
   * @var string{'text', 'hidden'}
   */
  public string $type = 'text';
  public string $value = '';

  public function asString(): string
  {
    return match ($this->type) {
      'textarea' => '',
      default    => useLayout()->render('html/input/input', ['field' => $this], 'common')
    };
  }

  public function getAttributes(): array
  {
     return array_merge(parent::getAttributes(), ['type', 'value']);
  }

}