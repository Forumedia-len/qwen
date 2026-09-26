<?php

namespace AC\core\system\validators;


use AC\core\system\helpers\StringHelper;

/** Проверяет строку и её ограничения; HTML-кодирование включается только явно. */
class StringValidator extends Validator
{
  /**
   * @var int|array описывает длину для строки, проходящей валидацию. Может быть определен следующими способами:
   * числом: точная длина, которой должна соответствовать строка;
   * массив с одним элементом: минимальная длина входящей строки (напр.: [8]). Это перезапишет min.
   * массив с двумя элементами: минимальная и максимальная длина входящей строки (напр.: [8, 128]). Это перезапишет и min, и max.
   */
  public $length;
  /**
   * @var integer - минимально допустимое значение, если меньше выдает ошибку
   */
  public $min;
  /**
   * @var integer - максимально допустимое значение, если больше выдает ошибку
   */
  public $max;
  /**
   * @var string - кодировка, по умолчанию utf-8
   */
  public $encoding;
  /**
   * @var bool - применить экранирование для xss атак
   */
  public $useShielding = false;
  /**
   * @var integer - максимально допустимое количество символов в верхнем регистре
   */
  public $max_up_case;
  /**
   * @var bool - в строке только числа
   */
  public $is_numeric;

  /**
   *  {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);
    if (is_array($this->length)) {
      if (isset($this->length[0])) {
        $this->min = $this->length[0];
      }
      if (isset($this->length[1])) {
        $this->max = $this->length[1];
      }
      $this->length = null;
    }
    if ($this->encoding === null) {
      $this->encoding = 'UTF-8';
    }

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
    if ($this->length !== null) {
      $this->addMessage(
        lang('error_attribute_length_value', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
          'value'     => ('<b>' . $this->length . '</b>')
        )),
        'length'
      );
    }
    if ($this->max_up_case !== null) {
      $this->addMessage(
        lang('error_attribute_max_up_case_value', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
          'value'     => ('<b>' . $this->max_up_case . '</b>')
        )),
        'max_up_case'
      );
    }
    if ($this->is_numeric !== null) {
      $this->addMessage(
        lang('error_attribute_is_numeric_value', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>')
        )),
        'is_numeric'
      );
    }
  }

  /**
   *  {@inheritDoc}
   */
  public function validateType($model)
  {
    parent::validateType($model);
    if ($this->strict && !is_string($model->{$this->getAttribute()})) {
      return false;
    }
    if ($model->{$this->getAttribute()} == null) {
      $model->{$this->getAttribute()} = '';
    }
    if (!is_string($model->{$this->getAttribute()})) {
      $this->addError($model);

      return false;
    }

    return true;
  }

  /**
   *  {@inheritDoc}
   */
  public function validateParams($model)
  {
    parent::validateParams($model);
    $value  = $model->{$this->getAttribute()};
    $length = mb_strlen($value ?? '', $this->encoding);
    if ($this->min !== null && $length < $this->min) {
      $this->addError($model, 'min');
    }
    if ($this->max !== null && $length > $this->max) {
      $this->addError($model, 'max');
    }
    if ($this->length !== null && $length !== $this->length) {
      $this->addError($model, 'length');
    }
    if ($this->max_up_case !== null && StringHelper::upperCount($value)) {
      $this->addError($model, 'max_up_case');
    }
    if ($this->is_numeric !== null && !is_numeric(trim($value))) {
      $this->addError($model, 'is_numeric');
    }

    if ($this->useShielding) {
      $model->{$this->getAttribute()} = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
    }
  }
}
