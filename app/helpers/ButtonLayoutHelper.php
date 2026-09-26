<?php

namespace AC\app\helpers;

use AC\core\system\helpers\ObjectHelper;
use AC\core\system\object\entity\html\Button;

class ButtonLayoutHelper
{
  public static function getInputButton(Button $button): string
  {
    return useLayout()->render('html\input\button', [
      'field' => $button], 'common');
  }

}