<?php

namespace AC\core\system\validators;

use AC\core\system\helpers\NumberHelper;

/** Проверяет числовое значение с поддержкой десятичной запятой. */
class FloatValidator extends NumericValidator
{
  /** Проверяет тип и преобразует число, сохраняя прежний режим для моделей. */
  public function validateType($model)
  {
    $attribute         = $this->getAttribute();
    if ($this->strict) {
      $value = $model->$attribute;
      if (!is_string($value) && !is_int($value) && !is_float($value)) {
        return false;
      }
      $number = str_replace(',', '.', trim((string)$value));
      if (!is_numeric($number) || !is_finite((float)$number)) {
        return false;
      }
    }
    $model->$attribute = NumberHelper::float($model->$attribute);
    if (!parent::validateType($model)) {
      $model->$attribute = null;

      return false;
    }

    return true;
  }
}
