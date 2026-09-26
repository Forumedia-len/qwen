<?php

namespace AC\core\system\validators;

/** Проверяет и преобразует логическое значение. */
class BoolValidator extends Validator
{
  /**
   * {@inheritDoc}
   */
  public function validateType($model)
  {
    if (parent::validateType($model)) {
      $attribute = $this->getAttribute();
      if ($this->strict && !in_array($model->$attribute, [0, 1, '0', '1', false, true], true)) {
        return false;
      }
      $model->$attribute = (int) $model->$attribute;
      if((method_exists($model, 'isLoad') && !$model->isLoad($attribute)) || $model->$attribute !== 1) {
        $model->$attribute = 0;
      }

      return true;
    }

    return false;
  }
}
