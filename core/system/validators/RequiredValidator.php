<?php

namespace AC\core\system\validators;

/**
 * Class RequiredValidator
 *
 *  Если подано пустое значение, то присваивает ему значение по умолчанию
 */
class RequiredValidator extends Validator
{
  /**
   * {@inheritdoc}
   */
  public function validateType($model)
  {
    parent::validateType($model);
    $attribute = $this->getAttribute();
    if ($this->isEmptyValue($model->$attribute)) {
      return false;
    }

    return true;
  }
}