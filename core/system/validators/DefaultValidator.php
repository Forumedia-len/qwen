<?php

namespace AC\core\system\validators;

class DefaultValidator extends Validator
{
  public $value;

  /**
   * {@inheritDoc}
   */
  public function validateType($model)
  {
    parent::validateType($model);
    $attribute = $this->getAttribute();
    if ($this->isEmptyValue($model->$attribute)) {
      $model->$attribute = $this->value;
    }

    return true;
  }
}