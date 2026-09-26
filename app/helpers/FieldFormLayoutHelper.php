<?php

namespace AC\app\helpers;

use AC\core\system\object\entity\html\form\FieldForm;

class FieldFormLayoutHelper
{
  public static function input(FieldForm $field): string
  {
    return useLayout()->render('html/input', ['field' => $field], 'common');
  }
}