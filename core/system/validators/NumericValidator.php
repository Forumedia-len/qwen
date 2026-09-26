<?php

namespace AC\core\system\validators;

class NumericValidator extends Validator
{
  /**
   * @var integer - минимально допустимое значение, если меньше выдает ошибку
   */
  public $min;
  /**
   * @var integer - максимально допустимое значение, если больше выдает ошибку
   */
  public $max;

  /**
   * {@inheritdoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);
    $attribute = $this->getAttribute();
    if ($this->min !== null) {
      $this->addMessage(
        lang('error_attribute_min_value', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
          'value'     => ('<b>' . $this->min . '</b>')
        )),
        'min'
      );
    }

    if ($this->max !== null) {
      $this->addMessage(
        lang('error_attribute_max_value', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
          'value'     => ('<b>' . $this->max . '</b>')
        )),
        'max'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateType($model)
  {
    parent::validateType($model);
    $value = $model->{$this->getAttribute()};
    if (!is_numeric($value)) {
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function validateParams($model)
  {
    $value = $model->{$this->getAttribute()};
    if ($this->min !== null && $value < $this->min) {
      $this->addError($model, 'min');
    }
    if ($this->max !== null && $value > $this->max) {
      $this->addError($model, 'max');
    }
  }

}