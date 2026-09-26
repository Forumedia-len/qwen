<?php

namespace AC\core\system\validators;

use AC\core\system\helpers\EmailHelper;

class EmailValidator extends Validator
{
  /**
   *  {@inheritDoc}
   */
  public function validateType($model)
  {
    if (parent::validateType($model)) {
      if (EmailHelper::checkEmail($model->{$this->getAttribute()})) {
        return true;
      }
    }

    return false;
  }
}