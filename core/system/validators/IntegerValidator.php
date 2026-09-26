<?php

namespace AC\core\system\validators;


/** Проверяет целое число и его ограничения. */
class IntegerValidator extends NumericValidator
{
  /**
   * @var - допустимое количество символов
   */
  public $length;

  /**
   * {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);
    $attribute = $this->getAttribute();
    if ($this->length !== null) {
      $this->addMessage(lang('error_attribute_max_length_value', 'message_error', array(
        'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
        'value'     => ('<b>' . $this->length . '</b>')
      )), 'length');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function validateType($model)
  {
    $attribute = $this->getAttribute();
    if (parent::validateType($model)) {
      $value     = $model->$attribute;
      if (!preg_match('/^\s*[+-]?\d+\s*$/', $value)) {
        $this->addError($model);

        return false;
      }
      if ($this->strict) {
        $number = trim((string)$value);
        $digits = ltrim(ltrim($number, '+-'), '0');
        $limit = str_starts_with($number, '-') ? substr((string)PHP_INT_MIN, 1) : (string)PHP_INT_MAX;
        if (strlen($digits) > strlen($limit) || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
          return false;
        }
      }
      $model->$attribute = (int)$value;

      return true;
    }
    $model->$attribute = null;

    return false;
  }

  /**
   * {@inheritDoc}
   */
  public function validateParams($model)
  {
    parent::validateParams($model);
    $value  = (string)$model->{$this->getAttribute()};
    $length = mb_strlen($value);
    if ($this->length !== null && $length > $this->length) {
      $this->addError($model, 'length');
    }

  }

}
