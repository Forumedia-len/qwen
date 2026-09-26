<?php

namespace AC\core\system\validators;

use AC\core\system\helpers\ReCaptchaHelper;

class CaptchaV4Validator extends Validator
{
  public $score;

  /**
   *  {@inheritDoc}
   */
  public function initMessageToParams($model)
  {
    parent::initMessageToParams($model);
    $this->addMessage(lang('error_attribute_robots', 'message_error', array()), 'robots');
  }

  /**
   *  {@inheritDoc}
   */
  public function validateParams($model)
  {
    parent::validateParams($model);

    if (!ReCaptchaHelper::checkReCaptcha()) {
      $this->addError($model, 'robots');
    }
  }
}