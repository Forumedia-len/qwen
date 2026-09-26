<?php

namespace AC\core\system\validators;

use AC\core\system\db\Query;
use PDO;

class LoginValidator extends StringValidator
{
  public $targetTable = 'clients';
  public $unique      = true;

  /**
   *  {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);

    $attribute = $this->getAttribute();
    if ($this->unique) {
      $this->addMessage(
        lang('error_attribute_unique_login', 'message_error', array(
          'attribute' => ('<b>' . $model->getAttributeLabel($attribute) . '</b>')
        )),
        'unique_login'
      );
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
      $usl = '/^[a-zA-Z0-9-_\p{L}]+$/u';
      $param_name = $model->getAttributeLabel($this->getAttribute());
      if(USE_STEP_FORM_REGISTRATION){
        $usl = '/^[a-zA-Z0-9-_.@\p{L}]+$/u';
        $param_name = lang('parameter_registration_field_login_step_reg', 'registration_fields');
      }
      if (preg_match($usl, $value)) { // буквы цифры умлауты дефис и подчеркивания
        return true;
      }
      $this->addMessage(
        lang('error_attribute_type_login', 'message_error', array(
          'attribute' => ('<b>' . $param_name . '</b>'),
          'min'       => ('<b>' . $this->min . '</b>'),
          'max'       => ('<b>' . $this->max . '</b>'),
        )),
        'type'
      );
    }

    return false;
  }

  /**
   *  {@inheritDoc}
   *  @todo убрать из кода прямой запрос к базе данных
   *
   */
  public function validateParams($model)
  {
    parent::validateParams($model);
    $value = $model->{$this->getAttribute()};
    if ($this->unique && $model->{$model->getPrimaryKey()} == null) {
      $result = Query::sqlQuery(
        'select count(*) from ' . Query::$prefix . $this->targetTable . ' where ' . $this->getAttribute() . ' = "' . addslashes($value) . '"',
        array(),
        true,
        array('style' => PDO::FETCH_NUM)
      );
      list($cnt) = $result[0];
      if ($cnt > 0) {
       // $this->addError($model, 'unique_login');
       $param_name = $model->getAttributeLabel($this->getAttribute());
      if(USE_STEP_FORM_REGISTRATION){
        $param_name = lang('parameter_registration_field_login_step_reg', 'registration_fields');
      } 
      $model->addError(lang('error_attribute_unique_login', 'message_error', array(
          'attribute' => ('<b>' . $param_name . '</b>')
        )));  
      }
    }
  }


}