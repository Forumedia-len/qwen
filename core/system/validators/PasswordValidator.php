<?php

namespace AC\core\system\validators;



class PasswordValidator extends StringValidator
{
  public $confirmation = true;
  public $fieldConfirmation = 'password_confirmation';
  public $fieldTable = 'password_md5';
  public $functionSalt = 'md5';

  /**
   *  {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);

    $attribute = $this->getAttribute();
    if ($this->confirmation) {
      $this->addMessage(lang('error_attribute_confirmation', 'message_error', array(
        'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>'),
        'confirmation' => ('<b>' . $model->getAttributeLabel($this->fieldConfirmation) . '</b>'),
      )), 'confirmation');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateType($model)
  {
    $model->{$this->getAttribute()} = trim($model->{$this->getAttribute()});
    if (parent::validateType($model)) {
      $value = $model->{$this->getAttribute()};
      $f_name = $this->functionSalt;
      if($model->{$model->getPrimaryKey()} == null || ($value && ($this->confirmation && $model->{$this->fieldConfirmation}))) {
        $model->{$this->fieldTable} = $f_name($value);
      }
      return true;
    }

    return false;
  }

  /**
   *  {@inheritDoc}
   */
  public function validateParams($model)
  {
    parent::validateParams($model);
    $value  = $model->{$this->getAttribute()};
    if($this->confirmation && $value != $model->{$this->fieldConfirmation}) {
      $this->addError($model, 'confirmation');
    }
  }
}